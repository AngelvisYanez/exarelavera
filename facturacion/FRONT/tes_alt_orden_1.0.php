<?php	
/**
* @abstract Permite realizar la cancelacion de comprobantes por abonos
* @author Erik Niebla
* @version 1.0
* Fecha de creaciï¿½n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_produ.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Produ($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Produ;
/**
* Evita el reenvio 
*/

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($provAjax)){ 
   $data=filter_input_array(INPUT_GET);
   $data["Emp_Cod"]=$Ses_Emp_Cod;   
    $contar = $obBD_con1->getRowConsulta(14, $data, $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $data["limits"]=$pagination['limits'];
    if($contar['total']>0)
        $responce['rows'] =  $obBD_con1->getArrayConsulta(14, $data, $obBD_conexion);
    utf8_encode_deep($responce['rows']); 
    echo json_encode($responce);exit();
}
if(isset($proAjax)){
    $contar = $obBD_con1->getRowConsulta(1, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*', $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows'] = $obBD_con1->getArrayConsulta(1, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$pagination['limits'], $obBD_conexion);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);exit();
}
if(isset($matAjax)){
    $contar = $obBD_con1->getRowConsulta(2, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$Fin_Cod.'**'.$Ses_Suc_Cod, $obBD_conexion);	      
    $pagination= pages($contar['total'], $page, $rows);
    $responce=$pagination['data'];
    $responce['rows'] = $obBD_con1->getArrayConsulta(2, $search.'*'.$Ses_Emp_Cod.'*'.$op_opciones.'*'.$Fin_Cod.'*'.$pagination['limits'].'*'.$Ses_Suc_Cod, $obBD_conexion);
    utf8_encode_deep($responce['rows']);
    echo json_encode($responce);exit();
}
/* Datos generales del producto a producir */
if(isset($ajaxProd)){
    $Ite_Cod=$Pro_Cod;$ini=$hoy;
    $responce['success']=true;
    $kardex1 = $obBD_con1->getArrayConsulta(4,$ini.'*'.$Ite_Cod, $obBD_conexion);
    if(count($kardex1)==1 && $kardex1[0]['Saldo']!==0 && $kardex1[0]['Stock']!=0){         
        $kardex1[0]['Promedio']=round(($kardex1[0]['Saldo']/$kardex1[0]['Stock']),6);
    }else{
        $kardex1[0]['Promedio']=0;$kardex1[0]['Saldo']=0;$kardex1[0]['Stock']=0;
    }
    list($ann, $mes, $dia) = preg_split('![/.-]!',$ini);
    $kardex1[0]['Kar_Det']='<b>Saldo al '.$dia.', de '.mes($mes, 1).', '.$ann.'</b>';
    $responce['stocks']=$kardex1[0];
    
    
    $responce['prod'] = $obBD_con1->getRowConsulta(3,$Ite_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);
    $responce['mesclas'] = $obBD_con1->getArrayConsulta(7,$Ite_Cod, $obBD_conexion);
    $formulas = $obBD_con1->getArrayConsulta(8,$Ite_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);
    $total=count($responce['mesclas']);
    

    foreach ($formulas AS $row){
        $row['act1']=true;

        //OBTENER PRECIO PROMEDIO POR CADA PRODUCTO 
        $precioPromedio = $obBD_con1->getArrayConsulta(88,$row['Pro_Cod'], $obBD_conexion);
        $arrayKardex = array();
        
        if(count($precioPromedio)>0){
            $x=COUNT($precioPromedio);
            if($x == 1){
              if($precioPromedio[0]['Kar_Sal']*1!=0){
                          $precioPromedio[0]['Kar_Pre']=$precioPromedio[0]['Kar_Prs'];
                          $precioPromedio[0]['Kar_Ime']=$precioPromedio[0]['Kar_Ims'];
                      }
                      $precioPromedio[0]['Stock']= $precioPromedio[0]['Kar_Can']*1 - $precioPromedio[0]['Kar_Sal'];
                      $precioPromedio[0]['Saldo']= $precioPromedio[0]['Kar_Ims']*1 - $precioPromedio[0]['Kar_Ime'];
                      $precioPromedio[0]['Promedio']=$precioPromedio[0]['Saldo']/$precioPromedio[0]['Stock'];
                       
                      $arrayKardex[0]['Promedio'] = $precioPromedio[0]['Promedio'];
                    $arrayKardex[0]['Saldo'] = $precioPromedio[0]['Saldo'];
                    $arrayKardex[0]['Stock'] = $precioPromedio[0]['Stock'];
            }
            else{
            for($i=1;$i<$x;$i++){
                      if($precioPromedio[$i]['Kar_Sal']*1!=0){
                          $precioPromedio[$i]['Kar_Pre']=$precioPromedio[$i-1]['Promedio'];
                          $precioPromedio[$i]['Kar_Ime']=$precioPromedio[$i]['Kar_Pre']*$precioPromedio[$i]['Kar_Sal'];
                      }
                      $precioPromedio[$i]['Stock']=$precioPromedio[($i-1)]['Stock']*1+$precioPromedio[$i]['Kar_Can']*1-$precioPromedio[$i]['Kar_Sal'];
                      $precioPromedio[$i]['Saldo']= ($precioPromedio[($i-1)]['Saldo']*1) + ($precioPromedio[$i]['Kar_Ims']*1) - ($precioPromedio[$i]['Kar_Ime']);
                      $precioPromedio[$i]['Promedio']=($precioPromedio[$i]['Stock']!=0? ($precioPromedio[$i]['Saldo']/$precioPromedio[$i]['Stock']) : ($precioPromedio[($i-1)]['Promedio']));
                    }  
                    $arrayKardex[0]['Promedio'] = $precioPromedio[$x-1]['Promedio'];
                    $arrayKardex[0]['Saldo'] = $precioPromedio[$x-1]['Saldo'];
                    $arrayKardex[0]['Stock'] = $precioPromedio[$x-1]['Stock'];
                }
          }
          else{
            $arrayKardex[0]['Promedio']=0;$arrayKardex[0]['Saldo']=0;$arrayKardex[0]['Stock']=0;
          }
          //FIN OBTENER PRECIO PROMEDIO POR CADA PRODUCTO

          $row['Stk_Prp'] = round($arrayKardex[0]['Promedio'],4);
          $row['Stk_Tot'] = round($row['Mes_Can'] * $row['Stk_Prp'], 4);

        for($i=0;$i<$total;$i++){
            if($row['Mes_Cod']==$responce['mesclas'][$i]['Mes_Cod']){
                $responce['mesclas'][$i]['formula'][]=$row;
            }            
        }
    }



    $responce['options']='<option value="">Ninguna</option>';
    foreach ($responce['mesclas'] AS $row){
        $responce['options']=$responce['options'].'<option value="'.$row['Mes_Cod'].'">'.$row['Mes_Nom'].'</option>';
    }
    utf8_encode_deep($responce);
    echo json_encode($responce);exit();
}
/* Guardar el Formulario */
if(isset($saveForm)){        
    $responce['success']=false;
    $egresos =  $obBD_con1->getArrayConsulta(15, $Ses_Emp_Cod, $obBD_conexion);    
    if(count($egresos)==0){$responce['message']='Configure Varios Egresos';echo json_encode($responce);exit();}
    $rs_vendedor = $obBD_con1->getArrayConsulta(16, $Ses_Prs_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);
    if(count($rs_vendedor)==0){$responce['message']='No tiene Permisos de Vendedor';echo json_encode($responce);exit();}
    $Prv_Cod=$egresos[0]['Prv_Cod'];
    $Vnd_Cod=$rs_vendedor[0]['Vnd_Cod'];$Aju_Hor=date ("H:i:s");
    $data=filter_input_array(INPUT_POST);
    $obBD_con1->inicio_transaccion($obBD_conexion->conexion);    
    $obBD_con1->grabarv_registros(sentencias_produ(9,$data),$obBD_conexion->conexion);//Cabecera Orden
    $Ord_Cod = $obBD_con1->insercionid ($obBD_conexion->conexion);
    $obBD_con1->grabarv_registros(sentencias_produ(18,$obBD_con1->parametros($Tia_Cod_Ing.'*'.$Vnd_Cod.'*'.$Prv_Cod.'*'.$Ord_Fec.'*'.$Aju_Hor.'*'.'Produccion Ingreso Producto'.'*'.'')),$obBD_conexion->conexion);//Cabecera Ajuste Ingreso
    $Aju_Cod_Ing = $obBD_con1->insercionid ($obBD_conexion->conexion);    
    $obBD_con1->grabarv_registros(sentencias_produ(18,$obBD_con1->parametros($Tia_Cod_Egr.'*'.$Vnd_Cod.'*'.$Prv_Cod.'*'.$Ord_Fec.'*'.$Aju_Hor.'*'.'Produccion Egreso Materia Prima'.'*'.'')),$obBD_conexion->conexion);//Cabecera Ajuste Egreso
    $Aju_Cod_Egr = $obBD_con1->insercionid ($obBD_conexion->conexion);
    $obBD_con1->grabarv_registros(sentencias_produ(19,$obBD_con1->parametros($Aju_Cod_Ing.'*'.$Pro_Cod.'*'.$Ord_Res.'*'.$Ord_Cou.'*'.$Ord_Tot)),$obBD_conexion->conexion);/* Detalle del Ajuste de Ingreso */
    $obBD_con1->grabarv_registros(sentencias_produ(20,$obBD_con1->parametros('0*'.$Aju_Cod_Ing.'*'.$Vnd_Cod.'*0*'.$Pro_Cod.'*'.$Ord_Fec.'*'.$Aju_Hor.'*'.$Ord_Res.'*0*0*'.$Ord_Cou.'*0*'.$Ord_Tot.'*0*'.$Iva_Cod.'*0')),$obBD_conexion->conexion);/* Ingreso a Kardex */
    /* Actualizar la tabla Stock */
    $rs_stock = $obBD_con1->getRowConsulta(21, $Pro_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);
    //var_dump($rs_stock);
    if($rs_stock!=NULL){
        $ImpTot=($rs_stock['Stk_Can']*($rs_stock['Stk_Prp']==null||$rs_stock['Stk_Prp']==''?0:$rs_stock['Stc_Prp'])+$Ord_Tot*1);
        $CanTot=$rs_stock['Stk_Can']+$Ord_Res;
        $obBD_con1->grabarv_registros(sentencias_produ(22,$obBD_con1->parametros($Pro_Cod.'*'.$Ses_Suc_Cod.'*'.$CanTot.'*'.($ImpTot/$CanTot))),$obBD_conexion->conexion);/* Ingreso a Kardex */
    }else{        
        $obBD_con1->grabarv_registros(sentencias_produ(23,$obBD_con1->parametros($Pro_Cod.'*'.$Ses_Suc_Cod.'*'.$Ord_Res.'*'.($Ord_Tot/$Ord_Res))),$obBD_conexion->conexion);/* Ingreso a Kardex */
    }    
    
    // Recorrer el Detalle
    $conteo=0;    
    foreach ($data['saveForm'] AS $row){
        $conteo++;$row['conteo']=$conteo;
        $row['Ord_Cod']=$Ord_Cod;
        if($row['Mes_Can']=='') $row['Mes_Can']=0;
        if($row['Stk_Prp']=='') $row['Stk_Prp']=0;
        
        $obBD_con1->grabarv_registros(sentencias_produ(10,$row),$obBD_conexion->conexion);//Detalle Orden
        $obBD_con1->grabarv_registros(sentencias_produ(19,$obBD_con1->parametros($Aju_Cod_Egr.'*'.$row['Pro_Cod'].'*'.$row['Mes_Can'].'*'.$row['Stk_Prp'].'*'.round($row['Mes_Can']*$row['Stk_Prp'],2))),$obBD_conexion->conexion);/* Detalle del Ajuste de Egreso */
        $obBD_con1->grabarv_registros(sentencias_produ(20,$obBD_con1->parametros('0*'.$Aju_Cod_Egr.'*'.$Vnd_Cod.'*0*'.$row['Pro_Cod'].'*'.$Ord_Fec.'*'.$Aju_Hor.'*0*'.$row['Mes_Can'].'*'.$row['Stk_Prp'].'*0*'.round($row['Mes_Can']*$row['Stk_Prp'],2).'*0*0*'.$Iva_Cod.'*0')),$obBD_conexion->conexion);/* Egreso a Kardex */
        /* Actualizar la tabla Stock */
        $rs_stock = $obBD_con1->getRowConsulta(21, $row['Pro_Cod'].'*'.$Ses_Suc_Cod, $obBD_conexion);
        $ImpTot=($rs_stock['Stk_Can']*($rs_stock['Stk_Prp']==null||$rs_stock['Stk_Prp']==''?0:$rs_stock['Stc_Prp'])-round($row['Mes_Can']*$row['Stk_Prp'],2));
        $CanTot=$rs_stock['Stk_Can']-$row['Mes_Can'];
        $obBD_con1->grabarv_registros(sentencias_produ(22,$obBD_con1->parametros($row['Pro_Cod'].'*'.$Ses_Suc_Cod.'*'.$CanTot.'*'.($ImpTot/$CanTot))),$obBD_conexion->conexion);/* Ingreso a Kardex */
    }
    $responce['link']="fac_pri_ord_prod_1.0.php?Ord_Cod=$Ord_Cod";
    
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);

    if($obBD_con1->Error==0){
      $responce['success']=true;
    }
    else{
      $responce['success']=false; 
      $responce['message']=$obBD_con1->MsgError;
    }
    echo json_encode($responce);
    exit();
}

?>
<!DOCTYPE html>
<HTML>
	<HEAD>		
                <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
                <TITLE><?Php echo "Prod. Registrar Orden [EXA]"; ?></TITLE>
                <meta charset= "UTF-8">
                <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>              
                <style>                     
                     
                </style>
	</HEAD>
<BODY>
 
    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Crear Orden de ProducciÃ³n</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            
                <div class="row">
                   
                    <div class="col-xs-12">
                       <form class="form-horizontal normal"    >
  
                        <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">ArtÃ­culo a Producir:</legend> <!-- Form Name -->
                              <div class="row">                                  
                                  <div class="col-xs-4">
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-3 control-label label-xs ">DescripciÃ³n:</label>  
                                          <div class="col-sm-8">  
                                            <div class="input-group input-group-xs">                                                                                                
                                                <input id="producto"  type="text" class="form-control" placeholder="Seleccione un Producto ..." required readonly />
                                                <span class="input-group-btn">
                                                    <button class="btn btn-success btn-frm" onclick="$('#proDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Proveedor"></span></button>
                                                </span>
                                              </div><!-- /input-group -->                                 
                                          </div>    
                                        </div>                                      
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-3 control-label label-xs ">Marca:</label>  
                                          <div class="col-sm-8">                                    
                                              <span  class="form-control input-xs" id="pro_mar"></span>                              
                                          </div>                                  
                                        </div>
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-3 control-label label-xs ">AdquisiciÃ³n:</label>  
                                          <div class="col-sm-8">                                    
                                              <span  class="form-control input-xs" id="pro_adq"></span>                              
                                          </div>                                  
                                        </div>
                                  </div>
                                  <div class="col-xs-4">
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-3 control-label label-xs ">Categoria:</label>  
                                          <div class="col-sm-8">                                    
                                              <span  class="form-control input-xs" id="pro_cat"></span>                              
                                          </div>                                  
                                        </div>                                      
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-3 control-label label-xs ">Cod. Cat.:</label>  
                                          <div class="col-sm-8">                                    
                                              <span  class="form-control input-xs" id="cat_cod"></span>                              
                                          </div>                                  
                                        </div>
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-3 control-label label-xs ">ObservaciÃ³n:</label>  
                                          <div class="col-sm-8">                                    
                                              <span  class="form-control input-xs" id="pro_obs"></span>                              
                                          </div>                                  
                                        </div>
                                  </div>
                                  <div class="col-xs-4">
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-4 control-label label-xs ">Stock:</label>  
                                          <div class="col-sm-8">                                    
                                              <span  class="form-control input-xs txtRight" id="pro_stk"></span>                              
                                          </div>                                  
                                        </div>                                      
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-4 control-label label-xs ">Prec Prom.:</label>  
                                          <div class="col-sm-8">                                    
                                              <span  class="form-control input-xs txtRight" id="pro_pre"></span>                              
                                          </div>                                  
                                        </div>
                                      <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-4 control-label label-xs ">Saldo Actual:</label>  
                                          <div class="col-sm-8">                                    
                                              <span  class="form-control input-xs txtRight" id="pro_sal"></span>                              
                                          </div>                                  
                                        </div>
                                  </div>
                                  <div class="col-xs-4">
                                      
                                  </div>
                                  <div class="col-xs-4">
                                      
                                  </div>
                              </div>  
                              

                               
                        </fieldset> 
                        
                                                           
                           
                                
                        </form>  
                    </div>
                    <div class="col-xs-12" style="min-height: 350px;">
                        <form id="formKardex" class="form-horizontal normal"  action="javascript:guardar()"   >
                             <input type="text" name="Pro_Cod" id="Pro_Cod" value="" style="display: none" /> 
                             <input type="text" name="Iva_Cod" id="Iva_Cod" value="" style="display: none" /> 
                        <div class="row">  
                           
                                <div class="col-xs-4">
                                    <fieldset class="exa-fieldset">                           
                                        <legend class="Titulos2">Formula:</legend> <!-- Form Name -->
                                        <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-3 control-label label-xs ">Formula:</label>  
                                          <div class="col-sm-9">   
                                              <select id="Mes_Cod" name="Mes_Cod" type="text" value="" class="form-control input-xs" required="" onchange="SelectMescla()" required="" >
                                                  <option value="">Ninguna</option>
                                              </select>
                                          </div>                                  
                                        </div>
                                        <!-- static input-->
                                        <div class="form-group">                                          
                                          <label class="col-sm-3 control-label label-xs ">Mesclador(es):</label>  
                                          <div class="col-sm-9">                                    
                                              <input type="text" id="Ord_Mes"  name="Ord_Mes" class="form-control input-xs" style="" value="" required="" />                              
                                          </div>                                         
                                        </div>
                                    </fieldset>  
                                    <fieldset class="exa-fieldset">                           
                                        <legend class="Titulos2">Datos Generales:</legend> <!-- Form Name -->
                                        <!-- static input-->
                                        <div class="form-group">                                          
                                          <label class="col-sm-4 control-label label-xs ">Fecha:</label>  
                                          <div class="col-sm-4">                                    
                                              <input type="text" id="Ord_Fec"  name="Ord_Fec" class="form-control input-xs center" style="" value="" required="" />                              
                                          </div>                                         
                                        </div>
                                        <!-- static input-->
                                        <div class="form-group">                                          
                                          <label class="col-sm-4 control-label label-xs ">Cliente:</label>  
                                          <div class="col-sm-8">
                                              <div class="input-group input-group-xs">                                                
                                                <input type="text" name="Cli_Cod" id="PrvCodBus" value="" style="display: none" />  
                                                <input id="docu" name="Provee" type="text" class="form-control" placeholder="Seleccione un Cliente ..." required readonly />
                                                <span class="input-group-btn">
                                                    <button class="btn btn-success btn-frm" onclick="$('#provDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Proveedor"></span></button>
                                                </span>
                                              </div><!-- /input-group --> 
                                              <!--<input type="text" class="form-control input-xs" style="" value="" required="" />                              -->
                                          </div>                                         
                                        </div>
                                        <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-4 control-label label-xs ">DescripciÃ³n:</label>  
                                          <div class="col-sm-8"> 
                                              <textarea id="Mes_Des" name="Ord_Obs" class="form-control input-xs"></textarea>
                                          </div>                                  
                                        </div>
                                    </fieldset>      
                                    <fieldset class="exa-fieldset">                           
                                        <legend class="Titulos2">Cantidad a Producir:</legend> <!-- Form Name -->
                                        
                                        <!-- static input-->
                                        <div class="form-group">
                                          
                                          <label class="col-sm-5 control-label label-xs ">Cant. a Producir:</label>  
                                          <div class="col-sm-3">                                    
                                              <input type="text"  id="Ord_Res" name="Ord_Res" class="form-control input-xs" style="text-align:right" value="1" required="" />                              
                                          </div>
                                          <div class="col-sm-3">                                    
                                              <span  class="input-xs disabled uni_des" >UNIDAD(ES)</span>
                                          </div>
                                        </div> 
                                        <!-- static input-->
                                        <div class="form-group">
                                          
                                            <label class="col-sm-5 control-label label-xs ">Max. Cant. Lote:</label>  
                                          <div class="col-sm-3">                                    
                                              <input type="text"  id="Ord_Max" name="Ord_Max" class="form-control input-xs" style="text-align:right" value="1" readonly="" required="" />                              
                                          </div>
                                          <div class="col-sm-3">                                    
                                              <span  class="input-xs disabled uni_des" >UNIDAD(ES)</span>
                                          </div>
                                        </div>  
                                        <!-- static input-->
                                        <div class="form-group">
                                          <label class="col-sm-5 control-label label-xs ">Lotes a Producir:</label>  
                                          <div class="col-sm-3">    
                                              <input type="text" id="Ord_Lot" name="Ord_Lot" class="form-control input-xs" style="text-align:right" value="1" readonly=""  required=""/>
                                          </div> 
                                          
                                          <div class="col-sm-3">                                    
                                              <span  class="input-xs disabled">LOTES</span>
        
                                          </div>
                                        </div>                                         
                                        <!-- static input-->
                                        <div class="form-group">
                                          
                                          <label class="col-sm-5 control-label label-xs ">Costo Unitario:</label>  
                                          <div class="col-sm-4">
                                              <div class="input-group input-group-xs">
                                                    <span class="input-group-addon"> $ </span>
                                                    <input type="text"  id="Ord_Cou" name="Ord_Cou" class="form-control input-xs" style="text-align:right" value="1" onchange="calcCosto()" required />                              
                                              </div>          
                                          </div>                                          
                                        </div>  
                                        <!-- static input-->
                                        <div class="form-group">
                                          
                                          <label class="col-sm-5 control-label label-xs ">Costo Total:</label>  
                                          <div class="col-sm-4"> 
                                               <div class="input-group input-group-xs">
                                                    <span class="input-group-addon"> $ </span>
                                                    <input type="text"  id="Ord_Tot" name="Ord_Tot" class="form-control input-xs" style="text-align:right" value="1" required="" readonly="" />                              
                                               </div>     
                                          </div>                                          
                                        </div> 
                                        
                                        <!-- static input-->
                                        <div class="form-actions center" style="padding-top: 5px;">
                                            <button class="btn btn-success btn-sm btn-frm" type="submit"><span class="glyphicon glyphicon-floppy-disk" title="Buscar Proveedor"></span> Guardar</button>
                                            <button class="btn btn-success btn-sm btn-new" type="button" onclick="resetForm()" disabled><span class="glyphicon glyphicon-check" title="Nuevo Registro"></span> Nuevo</button>
                                        </div>
                                    </fieldset>
                                </div>
                           <?php $tipo_ajus=$obBD_con1->getArrayConsulta(17, $Ses_Emp_Cod, $obBD_conexion); ?>
                            <div class="col-xs-8">
                                <fieldset class="exa-fieldset">                           
                                        <legend class="Titulos2">Movimientos de Inventario:</legend> <!-- Form Name -->
                                    <div class="row">
                                        <div class="col-sm-6">                                    
                                            <div class="form-group">
                                                <label class="col-sm-4 control-label label-xs ">Ingreso Inv.:</label>  
                                                <div class="col-sm-8">   
                                                    <select id="Tia_Cod_Ing" name="Tia_Cod_Ing" type="text" class="form-control input-xs" required="" required="" >
                                                        <?php foreach($tipo_ajus as $tip) if($tip['Tia_Tra']=='I'){ ?>
                                                        <option value="<?php echo $tip['Tia_Cod']; ?>" 
                                                          <?php if(startsWith($tip['Tia_Des'],"Producci")) echo 'selected'; ?>>
                                                          <?php echo $tip['Tia_Des']; ?>
                                                            
                                                          </option>
                                                        <?php } ?>
                                                    </select>
                                                </div>                                  
                                              </div>
                                        </div>
                                        <div class="col-sm-6">                                    
                                             <div class="form-group">
                                                <label class="col-sm-4 control-label label-xs ">Egreso Inv.:</label>  
                                                <div class="col-sm-8">   
                                                    <select id="Tia_Cod_Egr" name="Tia_Cod_Egr" type="text" class="form-control input-xs" required="" required="" >
                                                        <?php foreach($tipo_ajus as $tip) if($tip['Tia_Tra']=='E'){ ?>
                                                        <option value="<?php echo $tip['Tia_Cod']; ?>" <?php if(startsWith($tip['Tia_Des'],"Producci")) echo 'selected'; ?>><?php echo $tip['Tia_Des']; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>                                  
                                              </div>
        
                                        </div>
                                    </div>    
                                </fieldset>        
                                <table id="prods"></table>
                                <div id="prodsPager"></div>
                                <div style="padding-top: 10px;">
                                    <button class="btn btn-success btn-sm btn-frm" onclick="$('#matDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Proveedor"></span> Seleccione Materia Prima</button>
                                </div>
                            </div>
                        </div>  
                        </form>    
                        <script>
                            var mesclas;
                            var kardexGrid;
                            $(document).ready(function () {
                                $.createDatePickers('#Ord_Fec');
                                kardexGrid=$("#prods");
                                kardexGrid.jqGrid({
                                    url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
                                    mtype: "GET", datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },
                                    //postData: $("#form1").getData("ajaxGrid"),
                                    autowidth : true, shrinkToFit: true, height: 270,responsive:true,
                                    caption:'Listado de Materias Prima',hidegrid:false,
                                    cmTemplate: {sortable:false /*,editrules: {edithidden: true}*/},
                                    colModel: [                               
                                        { label: 'Cod.Int.', name: 'Pro_Cod', key: true, hidden:false,viewable:true, width: 20,align:'center' }, 
                                       
                                        { label: 'Detalle',name: 'Ite_Lar', width: 200},   
                                        { label: 'Stock',name: 'Stk_Can', width: 20,align:'right'}, 
                                        { label: 'Cant.',name: 'Mes_Can', width: 20,classes:'columnHighlight2',editable:true,align:'right',editoptions: {dataInit:function(e){ e.style.textAlign = 'right';e.style.paddingRight = '5px';}}},
                                        { label: '+/-', name: 'Mes_Can_Mas', width: 20,align:'right', formatter:'textboxExa', formatoptions:{type:'number', decimals:8, dataEvents:{ keyup:'updateRowItem(this.dataset);'}} },
                                        { label: 'Unid.',name: 'Uni_Des', width: 20}, 
                                        { label: 'C.Unit.',name: 'Stk_Prp', width: 30,align:'right'}, 
                                        { label: 'C.Total',name: 'Stk_Tot', width: 30,align:'right',formater:'currency'}, 
                                        //{ label: 'C. Unit.',name: 'Doc', width: 20,classes:'columnHighlight2'},
                                        //{ label: 'C. Total',name: 'Doc', width: 20,classes:'columnHighlight2'},
                                        { label:'&nbsp;', name: 'act1', width: 15, align: 'center',viewable: false,                                            
                                            formatter:function (cellvalue, options, rowObject) { 
                                                if(cellvalue===true)
                                                    return  '';
                                                else    
                                                    return  '<button type="button" class="btn btn-danger btn-xs btn-frm" title="Eliminar" onclick="$(\'#prods\').jqGrid(\'delRowData\',\''+rowObject.Pro_Cod+'\');"><i class="glyphicon glyphicon-trash"></i></button>'; 
                                               
                                            }
                                        },
                                        { label: 'Iva Cod', name: 'Iva_Cod', hidden:true}
                                                                                 
                                    ],     
                                    footerrow: false, userDataOnFooter: false,
                                    rowNum: 10000000, pager: "#kardexPager", gridview: true, rownumbers: true, viewrecords: true, pgbuttons: false,pgtext: null                         
                                });

                                $('#Ord_Res').on('change',function (){
                                    var mescla=getMescla($('#Mes_Cod').val()),grid=$('#prods');
                                    if(mescla!==null){ 
                                        $("#Ord_Lot").val(Math.ceil(this.value/mescla['Mes_Max']));
                                        for(var i=0;i<mescla['formula'].length;i++){
                                          var row=$.extend({},grid.jqGrid('getRowData',mescla['formula'][i]['Pro_Cod']));
                                          if(row['Mes_Can_Mas'] == ""){row['Mes_Can_Mas'] = 0;}
                                            grid.jqGrid("setCell", mescla['formula'][i]['Pro_Cod'], "Mes_Can", (parseFloat(mescla['formula'][i]['Mes_Can']) + parseFloat(row['Mes_Can_Mas']))*parseFloat(this.value));
                                            
                                        }                                        
                                    }
                                    calcCosto();
                                });
                            });
                        </script>    
                    </div>  
                </div> 
        </div>
    </div>
<!--INICIO DEL DIALOGO BUSCAR CUENTA--> 
    <div id="proDialog" title="B&uacute;squeda de Productos">  
        <form class="form-horizontal normal"> 
        <fieldset>
		<legend>Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-md-5 radioset" >
                          <input id="radc1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radc1">&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;</label>
                          <input id="radc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="radc2">&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;</label>                          
                    </div>                  
                       
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">B&uacute;squeda:</label>  
                    <div class="col-md-7" >
                        <div class="input-group">                        
                        <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese bÃºsqueda..." autofocus  class="form-control input-sm "/>
                        <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Producto" ><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                      </div><!-- /input-group --> 
                    </div>                    
                </div>
        </fieldset>  
       </form> 
    </div> 
<!-- FIN DEL DIALOGO CUENTAS--> 
<!--INICIO DEL DIALOGO BUSCAR CUENTA--> 
    <div id="matDialog" title="B&uacute;squeda de Materia Prima">  
        <form class="form-horizontal normal"> 
            <input type="text" id="Fin_Cod" name="Fin_Cod" style="display:none" value="" />
        <fieldset>
		<legend>Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-md-5 radioset" >
                          <input id="radcd1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radcd1">&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;</label>
                          <input id="radcd2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="radcd2">&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;</label>                          
                    </div> 
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">B&uacute;squeda:</label>  
                    <div class="col-md-7" >
                        <div class="input-group">                        
                        <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese bÃºsqueda..." autofocus  class="form-control input-sm "/>
                        <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Producto" ><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                      </div><!-- /input-group --> 
                    </div>                    
                </div>
        </fieldset>  
       </form> 
    </div> 
<!-- FIN DEL DIALOGO CUENTAS-->  
<!--INICIO DEL DIALOGO IMPRIMIR --> 
    <div id="successDialog"  title="Mensaje del Sistema">  
        <center><h4>La Orden de ProducciÃ³n se ha registrado con Exito!</h4></center>  
        <center> 
            <button type="button" onclick="$('#successDialog').dialog('close');" class="btn btn-inverse fileinput-button" style="display: inline;" >
                    <i class="icon-ban-circle icon-white"></i>
                    <span>Cerrar</span>
             </button>            
            <a id="impCompr" target="_blank" href=""  style="display: inline;" title="Imprimir Orden de ProducciÃ³n"><span  class="btn btn-primary start"> <i class="icon-print icon-white"></i> <span>Imprimir</span></span> </a>
               
        </center>        
    </div>
<!--INICIO DEL DIALOGO BUSCAR PROVEEDOR--> 
    <div id="provDialog" title="BÃºsqueda de Clientes">  
      <form class="form-horizontal normal"> 
        <fieldset>
		<legend>Filtros</legend>
                <div class="form-group">
                    <label class="col-md-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-md-8 radioset" >
                          <input id="rad1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="rad1">&nbsp;&nbsp;Apellido&nbsp;&nbsp;</label>
                          <input id="rad2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="rad2">&nbsp;&nbsp;CÃ©dula/R.U.C.&nbsp;&nbsp;</label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">B&uacute;squeda:</label>
                    <div class="col-md-7" >                 
                      <div class="input-group">                        
                        <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese cliente a buscar..." autofocus class="form-control input-sm " /><input type="text" style="display:none"/>
                        <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Cliente" ><span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button></span>
                      </div><!-- /input-group -->                          
                    </div>                    
                </div>
        </fieldset>  
       </form>    
    </div>
    <script type="text/javascript">
        $(document).ready(function() {               
                $.createSearchDialog('#provDialog',[
                        { label: 'CÃ³d.Int.', name: 'Cli_Cod', key: true,hidden:true,viewable: true },                                
                        { label: 'CÃ©dula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
                        { label: 'Proveedor', name: 'cliente', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
                        { label: 'DirecciÃ³n', name: 'Prs_Dir',hidden:true,viewable: true },                      
                            { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                                formatter:function (cellvalue, options, rowObject) { 
                                    var clic='selectProvee($("#provGrid").jqGrid("getRowData",'+rowObject.Cli_Cod+'))';
                                    return  '<span class="btn btn-success btn-xs" title="Seleccionar" onclick=\''+clic+'\'><i class="glyphicon glyphicon-arrow-right"></span>'; 
                                }
                            }
                    ]);  
                $.createDialog('#successDialog',150,550);                       
        }); 
    </script>
<script>
        // DIALOG BUSCAR CUENTAS            
             $.createSearchDialog('proDialog',[
                    { label: 'C&oacute;d.Int.', name: 'Pro_Cod', key: true, width: 15,align:"center",hidden:true },                                
                    { label: 'DescripciÃ³n', name: 'Ite_Lar', width: 110 },                      
                    { label: 'Marca', name: 'Mar_Des', width: 40},
                    { label: 'Tipo', name: 'Cat_Des', width: 110,align:"center" },                    
                        { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 20, align: 'center',viewable: false,
                            formatter:function (cellvalue, options, rowObject) { 
                                return  '<span class="btn btn-success btn-xs" title="Enviar al Cr&eacute;dito" onclick="SelectProd(\''+rowObject.Pro_Cod+'\',\''+rowObject.Ite_Lar+'\');"><i class="glyphicon glyphicon-arrow-right"></i></span>'; 
                            }
                        }
                ]);
            $.createSearchDialog('matDialog',[
                    { label: 'C&oacute;d.Int.', name: 'Pro_Cod', key: true, width: 15,align:"center",hidden:true },                                
                    { label: 'Iva Cod', name: 'Iva_Cod', hidden:true},
                    { label: 'DescripciÃ³n', name: 'Ite_Lar', width: 110 },                      
                    { label: 'Marca', name: 'Mar_Des', width: 40},
                    { label: 'Unidad', name: 'Uni_Des', width: 40,hidden:true},
                    { label: 'Stock', name: 'Stk_Can', width: 40,hidden:true},
                    { label: 'Tipo', name: 'Cat_Des', width: 110,align:"center" },                    
                        { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 20, align: 'center',viewable: false,
                            formatter:function (cellvalue, options, rowObject) { 
                                return  '<span class="btn btn-success btn-xs" title="Enviar al Cr&eacute;dito" onclick="addFilaMat( $(\'#matGrid\').getRowData(\''+rowObject.Pro_Cod+'\'));"><i class="glyphicon glyphicon-arrow-right"></i></span>'; 
                            }
                        }
                ]); 
            function guardar(){
                var data=$('#formKardex').serializeObject();   
                if(data['Pro_Cod']===''){$.alert('Seleccione el Producto');return false;}
                data['saveForm']=$("#prods").getGridBatch();
                if(data['saveForm'].length===0){$.alert('Seleccione las Materias Prima');return false;}

                $.post("",data, function( response ) {
                    if(response['success']===true){
                        $('.btn-frm').attr('disabled','disabled');
                        $('.btn-new').removeAttr('disabled');
                        $('.btn-new').removeAttr('disabled');
                        $('#impCompr').attr('href',response['link']);                                                                
                        $('#successDialog').dialog('open');
                    }else{
                        $('.btn-frm').removeAttr('disabled');$.alert("No se Logro Guardar la InformaciÃ³n");$("#prods").startGridEdit();                                
                    }
                },'json').fail(function(error) { $('.btn-frm').removeAttr('disabled');$.alert("El Servidor ha fallado en responder!");$("#prods").startGridEdit(); })
                        .always(function() {});    

            }    
            function addFilaMat(data){
                var grid=$("#prods");
                if(!grid.existsId(data['Pro_Cod'])&& data['Pro_Cod']!==$('#Fin_Cod').val()){
                    data['Mes_Can']=1;                    
                    grid.jqGrid("addRowData", data["Pro_Cod"], data, "last");        
                    editGrid(grid);
                }else
                    $.alert('Ya se encuentra en el listado!');                
            }
            function editGrid(grid){
                grid.startGridEdit();
                var mescla=getMescla($('#Mes_Cod').val());
                if(mescla!==null){ 
                    for(var i=0;i<mescla['formula'].length;i++){
                        grid.jqGrid('saveRow', mescla['formula'][i]['Pro_Cod'], false, 'clientArray');
                    }
                }
            }
            function SelectMescla(){  
                resetMescla();
                var mescla=getMescla($('#Mes_Cod').val());
                if(mescla!==null){

                    var costoTotal = 0;
                    var i;

                    for (i = 0; i < mescla['formula'].length; i++) {
                      costoTotal += mescla['formula'][i]["Stk_Tot"];
                    }
                    $('#Ord_Cou').val(costoTotal.toFixed(4));

                    $("#Mes_Des").html(mescla['Mes_Des']);
                    $("#Ord_Res").val(mescla['Mes_Res']);
                    $("#Ord_Max").val(mescla['Mes_Max']);
                    $("#Ord_Lot").val(Math.ceil(mescla['Mes_Res']/mescla['Mes_Max']));                 
                    $("#prods").jqGrid('setGridParam', {data:(typeof mescla['formula']==='undefined'?[]:mescla['formula'])}).trigger('reloadGrid', [{ page: 1 }]);
                }
            }
            function getMescla(id){
                if(typeof mesclas === 'undefined'||mesclas.length===0) return null;
                for(var i=0;i<mesclas.length;i++)
                    if(mesclas[i]['Mes_Cod']===id)
                        return mesclas[i];
                return null;
            }
            function resetMescla(){
                $("#prods").clearGrid();
                $('#Mes_Des').html('');
                $('#Ord_Res').val(1);
                $('#Ord_Lot').val(1);
                $('#Uni_Des').html('');
                $('#Ord_Obs').val('');
            }
            function SelectProd(id,desc){
                $('#Pro_Cod').val(id);
                $('#Fin_Cod').val(id);
                $('#producto').val(desc);
                resetMescla();               
                $('#proDialog').dialog('close');
                $.get('<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',{'Pro_Cod':id,'ajaxProd':true}, function(response){
                    if(response['success']===true){
                        $('#pro_cat').html(response['prod']['Ite_Lar']);
                        $('#cat_cod').html(response['prod']['Pro_Cdc']);
                        $('#pro_obs').html(response['prod']['Pro_Obs']);
                        $('#Iva_Cod').val(response['prod']['Iva_Cod']);
                                                
                        $('#pro_mar').html(response['prod']['Mar_Des']);
                        $('#pro_adq').html(response['prod']['Adq_Des']);
                        $('.uni_des').html(response['prod']['Uni_Des']);
                        
                        $('#pro_stk').html(response['stocks']['Stock']);
                        $('#pro_pre').html(response['stocks']['Promedio']);
                        $('#pro_sal').html(response['stocks']['Saldo']);
                        
                        if($("#prods").existsId(response['prod']['Pro_Cod'])){
                            $("#prods").clearGrid();
                            $('#Ord_Res').val(1);
                            $('#Ord_Lot').val(1);
                        }
                        $('#Mes_Cod').html(response['options']);
                        mesclas=response['mesclas'];
                    }else {$.alert("No se logro obtener informacion del Producto!");}                                
                },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});;
               
            }
            function calcCosto(){
                $('#Ord_Tot').val(($('#Ord_Res').val()*$('#Ord_Cou').val()).toFixed(2));
            }
            function selectProvee(data){                           
                            if(typeof data==='undefined'){                                
                                $("input[name='Cli_Cod']").val('');
                                $("#docu").val('');                                
                                return false;
                            }else{                            
                                $("#docu").val(data['cliente']);                             
                                $("input[name='Cli_Cod']").val(data['Cli_Cod']);                                     
                                $("#provDialog").dialog("close");
                            }
                        }
            function resetForm(){
                    $('#formKardex')[0].reset();
                    $("#prods").clearGrid();                    
                    $('.btn-new').attr('disabled','disabled');
                    $('.btn-frm').removeAttr('disabled');                                
                    $("#prods").startGridEdit();
                    $('#Pro_Cod').val('');
                    $('#Fin_Cod').val('');
                    $('#producto').val('');
                    $('#pro_cat').html('');
                    $('#cat_cod').html('');
                    $('#pro_obs').html('');
                    $('#pro_mar').html('');
                    $('#pro_adq').html('');
                    $('#uni_des').html('');
                    $('#pro_stk').html('');
                    $('#pro_pre').html('');
                    $('#pro_sal').html('');
                    $('#Mes_Des').val('');
                }

          function updateRowItem(obj){
              var row=$.extend({},kardexGrid.jqGrid('getRowData',obj['rowId']));
              var mescla=getMescla($('#Mes_Cod').val()),grid=$('#prods');
              
              for(var i=0;i<mescla['formula'].length;i++){
                  if(row['Pro_Cod'] == mescla['formula'][i]['Pro_Cod']){

                    if(row['Mes_Can_Mas'] == ""){row['Mes_Can_Mas'] = 0;}
                    var newRow={Mes_Can:(parseFloat(mescla['formula'][i]['Mes_Can']) + parseFloat(row['Mes_Can_Mas']))};
                    kardexGrid.setCell(obj['rowId'],'Mes_Can',newRow['Mes_Can']);

                    var rowAct=$.extend({},kardexGrid.jqGrid('getRowData',obj['rowId']));
                    var newTot={Stk_Tot:(parseFloat(rowAct['Mes_Can']) * parseFloat(row['Stk_Prp'])).toFixed(4)};
                    kardexGrid.setCell(obj['rowId'],'Stk_Tot',newTot['Stk_Tot']);

                    var costoUnitario = updateCostoUnitario();
                    $("#Ord_Cou").val(costoUnitario.toFixed(4));
                    $("#Ord_Res").val("1");
                  }
              }  
          } 

          function updateCostoUnitario(){
            var row=$.extend({},kardexGrid.jqGrid('getRowData'));
            var costoUnitario = 0;
            var filas = Object.values(row);
            for(var i=0;i<filas.length;i++){
              costoUnitario += parseFloat(row[i]['Stk_Tot']);
            }
            return costoUnitario;
          }                   
                        
</script>                
</BODY>
</HTML>