<?php	
/**
* @abstract Permite realizar el control tributario
* @author Erik Niebla
* @version 1.0
* Fecha de creaci�n  2015-07-22
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_104.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

ini_set("memory_limit" , "32M") ;
/**
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Anx($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Anx; 

$hoy = date("Y-m-d");
$mes = date("m");

if(isset($save)){     
    $body103='';
    $body='<tr style="mso-number-format:\'0.00\';" align="right">
	    <td style="border:1px solid grey;border-right:2px solid grey;mso-number-format:\'@\';" align="left"><b>{mes}</b></td>                        
            <td style="border:1px solid grey;">{ventas15}</td>
            <td style="border:1px solid grey;">{ventas12}</td>
            <td style="border:1px solid grey;">{ventas8}</td>
            <td style="border:1px solid grey;">{ventas5}</td>
            <td style="border:1px solid grey;">{ventas0}</td>
            <td style="border:1px solid grey;"><a onclick=mostrarDetalle({ndInfo},"sub")>{ventasND}</a></td>
            <td style="border:1px solid grey;"><a onclick=mostrarDetalle({ncInfo},"sub")>{ventasNC}</a></td>
            <td style="border:1px solid grey;">{ventasIva}</td>
            <td style="border:1px solid grey;"><a onclick=mostrarDetalle({retVentaInfo},"ret")>{ven_fuen}</td>
            <td style="border:1px solid grey;"><a onclick=mostrarDetalle({ivaVentaInfo},"ret")>{609}</td>            
            <td style="border:1px solid grey;">{ventasTotal}</td>
            <td style="border:1px solid grey;">{compras15}</td>
            <td style="border:1px solid grey;">{compras12}</td>
            <td style="border:1px solid grey;">{compras8}</td>
            <td style="border:1px solid grey;">{compras5}</td>
            <td style="border:1px solid grey;">{compras0}</td>
            <td style="border:1px solid grey;"><a onclick=mostrarDetalle({ndInfoc},"sub")>{comprasND}</td>
            <td style="border:1px solid grey;"><a onclick=mostrarDetalle({ncInfoc},"sub")>{comprasNC}</td>
            <td style="border:1px solid grey;">{importaciones}</td>
            <td style="border:1px solid grey;">{comprasIva}</td> 
            <td style="border:1px solid grey;">{comprasTotal}</td>
            <td style="border:1px solid grey;">{VetCompr}</td>
            <td style="border:1px solid grey;">{721}</td>
            <td style="border:1px solid grey;">{723}</td>
            <td style="border:1px solid grey;">{725}</td>
            <td style="border:1px solid grey;">{727}</td>
            <td style="border:1px solid grey;">{729}</td>
            <td style="border:1px solid grey;">{731}</td>
            <td style="border:1px solid grey;border-right:2px solid grey;">{799}</td>    
            {datos103}
            <td style="border:1px solid grey;border-right:2px solid grey;">{total103}</td>
        </tr>';
    $iva='true';$noiva='false';            
    $tabla=array(
        '{data}'=>'','{ndInfo}'=>'','{ncInfo}'=>'','{retVentaInfo}'=>'','{ivaVentaInfo}'=>'','{ncInfoc}'=>'','{ndInfoc}'=>'','{totVent12}'=>0,'{totVent15}'=>0,'{totVent5}'=>0,'{totVent0}'=>0,'{totVentND}'=>0,'{totVentNC}'=>0,'{totVentIva}'=>0,'{totVentas}'=>0,'{totComp15}'=>0,'{totComp12}'=>0,'{totComp5}'=>0,'{totComp0}'=>0,'{totImport}'=>0,'{totCompIva}'=>0,'{totCompras}'=>0
    );
    for($mes=($fromMonth*1);$mes<=($toMonth*1);$mes++){
        $ini = $anio.'-'.str_pad($mes, 2, "0", STR_PAD_LEFT).'-'.'01 00:00:00';
	    $fin = $anio.'-'.str_pad($mes, 2, "0", STR_PAD_LEFT).'-'.ultimoDia($mes,$anio).' 23:59:59';
        
        $row_ivas = $obBD_con1->getRowConsulta(37,$ini, $obBD_conexion);
        $valorIva=(int)$row_ivas['Iva_Por'];
        $ncventas=array();
        $datos=array();
        $datos['{mes}']=mes($mes, 1);
        $datos['{mesNC}']=$mes;
        
        /*
         * Formulario 103          
         */
        //Codigos Retencion
        $rs_DatosCodigo=$obBD_con1->getArrayConsulta(45,$Ses_Emp_Cod.'*'.$From.'-01*'.$To.'-'.cal_days_in_month(CAL_GREGORIAN, $fromMonth, $anio),$obBD_conexion);
        $totRegistro=0;
        $totBase=0;
        $totRet=0;
        $optest='A'; //var_dump($rs_DatosCodigo);
        for($i=0;$i<count($rs_DatosCodigo);$i++){ 	     
            if($rs_DatosCodigo[$i]['Ren_Sri']!=='332'){
                $rs_DatosCompra=$obBD_con1->getArrayConsulta(17,$Ses_Emp_Cod.'*'.$rs_DatosCodigo[$i]['Ren_Sri'].'*'.$ini.'*'.$fin.'*'.$optest,$obBD_conexion);
            }else{
                $rs_DatosCompra=$obBD_con1->getArrayConsulta(18,$Ses_Emp_Cod.'*'.$ini.'*'.$fin.'*'.$optest,$obBD_conexion);			
            }//fin if($codigo['Ren_Sri']=='332')
	        $rs_DatosCompra_total=count($rs_DatosCompra);
           
            $totRegistro=$totRegistro+$rs_DatosCompra_total;
            $totBase=0;	   
            $totRet=0;

            foreach($rs_DatosCompra as $compra){
                    $nomDocComp=substr($compra['Tic_Des'],0,19);
                    $totBase=$totBase+$compra['Ret_Bas'];	
                    $totRet=$totRet+$compra['Ren_Ret'];
            }
            $rs_DatosCodigo[$i]['totBase']=$totBase;
            $rs_DatosCodigo[$i]['totRet']=$totRet;
           
        }
        //die(var_dump($rs_DatosCodigo));

        $form =array();        
        foreach($rs_DatosCodigo as $codigo){
            if(strlen($codigo['Ren_Sri'])>3){$CodSri=substr($codigo['Ren_Sri'], 0, 3);}else{$CodSri=$codigo['Ren_Sri'];}            
            if(!isset($form['{'.$CodSri.'}'])) {$form['{'.$CodSri.'}']=0;/*$form['{'.(($CodSri*1)+50).'}']=0;*/}
             
            $form['{'.$CodSri.'}']=formato_numero(($form['{'.$CodSri.'}']*1)+$codigo['totBase'],2,1);
            //$form['{'.(($CodSri*1)+50).'}']=formato_numero(($form['{'.(($CodSri*1)+50).'}']*1)+$codigo['totRet'],2,1);                                           
        }
        ksort($form);
        
        //var_dump($form);
        if($body103==''){
            $tabla['{header103Max}']=count($form)+1;
            $tabla['{colspanMax}']=count($form)+18;
            foreach($form as $key=>$value){
                $tabla['{header103}']=$tabla['{header103}'].'<td rowspan="2" style="border:1px solid grey;">'.str_replace("}","",str_replace("{","",$key)).'</td>';
                $body103=$body103.'<td style="border:1px solid grey;">'.$key.'</td>';
                //$tabla['{totalArray103}']=$body103;
                $tabla[$key]=0;
            }
        }
        $datos['{datos103}']=reporteArray($form,$body103);
        $form['{349}']=0;$form['{399}']=0;
        $form['{497}']=0;$form['{498}']=0;
        for($i=302;$i<=346;$i++){
            if(isset($form['{'.$i.'}'])){
                $form['{349}']= $form['{349}']+($form['{'.$i.'}']*1); 
                if(!isset($tabla['{'.$i.'}'])) $tabla['{'.$i.'}']=0;
                $tabla['{'.$i.'}']=formato_numero($tabla['{'.$i.'}']+($form['{'.$i.'}']*1),2,1);
            }
        }
        for($i=401;$i<=440;$i++){
            if(isset($form['{'.$i.'}'])){
                $form['{497}']= $form['{497}']+($form['{'.$i.'}']*1); 
                if(!isset($tabla['{'.$i.'}'])) $tabla['{'.$i.'}']=0;
                $tabla['{'.$i.'}']=formato_numero($tabla['{'.$i.'}']+($form['{'.$i.'}']*1),2,1);
            }
        }
        $datos['{total103}']=formato_numero($form['{349}']+$form['{497}'],2,1);        
        $tabla['{total103}']=formato_numero($tabla['{total103}']+$datos['{total103}'],2,1);
        /* 
         * Formulario 104 
         */
        //VENTAS
        //$vent_contado= $obBD_con1->getRowConsulta(15, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$iva*1*1", $obBD_conexion);
        //$vent_credito= $obBD_con1->getRowConsulta(15, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$iva*1*2", $obBD_conexion);        
        //$row_402 = $obBD_con1->getRowConsulta(44, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$iva*1", $obBD_conexion);
	    //$row_403 = $obBD_con1->getRowConsulta(13, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$noiva*1", $obBD_conexion);
        //$row_404 = $obBD_con1->getRowConsulta(44, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$noiva*1", $obBD_conexion);        

        $ventaIva  = $obBD_con1->getRowConsulta(46, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$iva*1", $obBD_conexion);        
        $venta0 = $obBD_con1->getRowConsulta(46, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$noiva*1", $obBD_conexion);        
        //VENTAS N/C
       /* $NC_contado= $obBD_con1->getRowConsulta(15, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$iva*4*1", $obBD_conexion);
        $NC_credito= $obBD_con1->getRowConsulta(15, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$iva*4*2", $obBD_conexion);  */
        //$NC_Ventas= $obBD_con1->getRowConsulta(40, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$iva*4", $obBD_conexion); 
        //$NC_contado= $obBD_con1->getRowConsulta(41, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$iva*4*1", $obBD_conexion);
        //$NC_credito= $obBD_con1->getRowConsulta(41, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$iva*4*2", $obBD_conexion); 
        
        //  3 .- 98.52 + 410.71 + 20.54 = 529.87 SIN IVA 

        /*$row_412 = $obBD_con1->getRowConsulta(44, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$iva*4", $obBD_conexion);
	    $row_413 = $obBD_con1->getRowConsulta(13, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$noiva*4", $obBD_conexion);
        $row_414 = $obBD_con1->getRowConsulta(44, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$noiva*4", $obBD_conexion);*/
        // NOTA: no se puede diferenciar cuando es venta de activos fijos
        
        /* COMPRAS  */

        $compras =$obBD_con1->getRowConsulta(47, $ini.'*'.$fin.'*'.$Ses_Emp_Cod, $obBD_conexion);

       /* $row_500 =$obBD_con1->getRowConsulta(12, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$iva*1~3*2~7", $obBD_conexion);
        $row_501 =$obBD_con1->getRowConsulta(12, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$iva*1~3*4", $obBD_conexion);
        $row_502 =$obBD_con1->getRowConsulta(12, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$iva*1~3*3", $obBD_conexion);        
        $row_507 =$obBD_con1->getRowConsulta(12, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$noiva*1~3~5", $obBD_conexion);  
        $row_508 =$obBD_con1->getRowConsulta(12, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$noiva*2", $obBD_conexion);  
        $row_sub5 =$obBD_con1->getRowConsulta(12, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$iva*1*2~4~7*5", $obBD_conexion); */
        /* COMPRAS N/C */
        /*$row_510 =$obBD_con1->getRowConsulta(12, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$iva*4*2~7", $obBD_conexion);
        $row_511 =$obBD_con1->getRowConsulta(12, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$iva*4*4", $obBD_conexion);
        $row_512 =$obBD_con1->getRowConsulta(12, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$iva*4*3", $obBD_conexion);
        $row_517 =$obBD_con1->getRowConsulta(12, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$noiva*4", $obBD_conexion);*/
        //$row_518 =$obBD_con1->getRowConsulta(12, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$noiva*4", $obBD_conexion);
        // NOTA: no se puede diferencia RISE
        //IMPORTACIONES
        $row_503 =$obBD_con1->getRowConsulta(36, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$iva*16*1~2*S", $obBD_conexion);
        $row_504 =$obBD_con1->getRowConsulta(36, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$iva*16*1~2~6~7*B", $obBD_conexion);
        $row_505 =$obBD_con1->getRowConsulta(12, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$iva*16*3~4", $obBD_conexion);
        $row_506 =$obBD_con1->getRowConsulta(12, $ini.'*'.$fin.'*'.$Ses_Emp_Cod."*$noiva*16", $obBD_conexion);
        //RETENCIONES
        $row_609 = $obBD_con1->getRowConsulta(10, $ini.'*'.$fin.'*'.$Ses_Emp_Cod, $obBD_conexion); //var_dump($row_609);
        $row_Fue = $obBD_con1->getRowConsulta(39, $ini.'*'.$fin.'*'.$Ses_Emp_Cod, $obBD_conexion); //var_dump($row_609);
        $row_Ret_Ban = $obBD_con1->getRowConsulta(59, $ini.'*'.$fin.'*'.$Ses_Emp_Cod, $obBD_conexion); //Retenciones Bancarias en Ventas

        $row_721 = $obBD_con1->getRowConsulta(9, $ini.'*'.$fin.'*'.'10'.'*'.$Ses_Emp_Cod, $obBD_conexion);	
	    $row_723 = $obBD_con1->getRowConsulta(9, $ini.'*'.$fin.'*'.'20'.'*'.$Ses_Emp_Cod, $obBD_conexion);	
	    $row_725 = $obBD_con1->getRowConsulta(9, $ini.'*'.$fin.'*'.'30'.'*'.$Ses_Emp_Cod, $obBD_conexion);
        $row_727 = $obBD_con1->getRowConsulta(9, $ini.'*'.$fin.'*'.'50'.'*'.$Ses_Emp_Cod, $obBD_conexion);
        $row_729 = $obBD_con1->getRowConsulta(9, $ini.'*'.$fin.'*'.'70'.'*'.$Ses_Emp_Cod, $obBD_conexion);
        $row_731 = $obBD_con1->getRowConsulta(9, $ini.'*'.$fin.'*'.'100'.'*'.$Ses_Emp_Cod, $obBD_conexion);
        
        /* COMPRAS */
        /*$datos['{500}']=formato_numero($row_500['Importe'],2,1); //Adquisiciones y pagos 12%
        $datos['{510}']=formato_numero($row_500['Importe']-$row_510['Importe'],2,1); //Adquisiciones y pagos 12% menos N/C
        $datos['{520}']=formato_numero(($datos['{510}']*$valorIva)/100,2,1);
        
        $datos['{501}']=formato_numero($row_501['Importe'],2,1); //Otras Adquisiciones y pagos con derecho 12%
        $datos['{511}']=formato_numero($row_501['Importe']-$row_511['Importe'],2,1); //Otras Adquisiciones y pagos con derecho 12% N/C
        $datos['{521}']=formato_numero(($datos['{501}']*$valorIva)/100,2,1);
        
        $datos['{502}']=formato_numero($row_502['Importe'],2,1); //Otras Adquisiciones y pagos sin derecho 12%
        $datos['{512}']=formato_numero($row_502['Importe']-$row_512['Importe'],2,1); //Otras Adquisiciones y pagos sin derecho 12% N/C
        $datos['{522}']=formato_numero(($datos['{502}']*$valorIva)/100,2,1);
        
        $datos['{503}']=formato_numero($row_503['Importe'],2,1); //Importaciones Servicios 12%
        $datos['{504}']=formato_numero($row_504['Importe'],2,1); //Importaciones Bienes 12%
        $datos['{505}']=formato_numero($row_505['Importe'],2,1); //Importaciones Activos 12%
        $datos['{506}']=formato_numero($row_506['Importe'],2,1); //Importaciones Bienes incluye activos 0%
        
        $datos['{513}']=$datos['{503}'];
        $datos['{514}']=$datos['{504}'];
        $datos['{515}']=$datos['{505}'];
        $datos['{516}']=$datos['{506}'];
        
        $datos['{507}']=formato_numero($row_507['Importe'],2,1);//Adquisiciones y pagos 0%
        $datos['{517}']=formato_numero($row_507['Importe']-$row_517['Importe'],2,1);//Adquisiciones y pagos 0% N/C
        
        $datos['{508}']=formato_numero($row_508['Importe'],2,1);//Adquisiciones a cont. RISE
        $datos['{518}']=formato_numero($row_508['Importe'],2,1);//Adquisiciones a cont. RISE N/C
        
       
        
        */
        
        /*$datos['{compras12}']=formato_numero($datos['{502}']+$datos['{501}']+$datos['{500}'],2,1);
        $datos['{compras5}']=formato_numero($row_sub5['Importe'],2,1);
        $datos['{compras0}']=formato_numero($datos['{507}']+$datos['{508}'],2,1);
        $datos['{comprasNC}']=formato_numero($row_510['Importe']+$row_511['Importe']+$row_512['Importe']+$row_517['Importe'],2,1);
        $datos['{comprasIva}']=formato_numero(($datos['{522}']+$datos['{521}']+$datos['{520}']+$row_sub5['Iva'])-$row_512['Iva'],2,1);
        $datos['{comprasTotal}']=formato_numero($datos['{compras12}']+$datos['{compras5}']+$datos['{compras0}']-$datos['{comprasNC}'],2,1); //se resto la nota de credito */

         $datos['{importaciones}']=formato_numero($datos['{513}']+$datos['{514}']+$datos['{515}']+$datos['{516}'],2,1);  

        $datos['{compras15}']=formato_numero($compras['sub15'],2,1);
        $datos['{compras12}']=formato_numero($compras['sub12'],2,1);
        $datos['{compras8}']=formato_numero($compras['sub8'],2,1);
        $datos['{compras5}']=formato_numero($compras['sub5'],2,1);
        $datos['{compras0}']=formato_numero($compras['sub0'],2,1);
        $datos['{comprasND}']=formato_numero($compras['subnd0']+$compras['subnd5']+$compras['subnd12']+$compras['subnd15'],2,1);
        $datos['{comprasNC}']=formato_numero($compras['subnc0']+$compras['subnc5']+$compras['subnc12']+$compras['subnc15'],2,1);
        $datos['{comprasIva}']=formato_numero($compras['Iva']-$compras['Ivanc'],2,1);
        $datos['{comprasTotal}']=formato_numero(($datos['{compras15}']+$datos['{compras12}']+$datos['{compras5}']+$datos['{compras0}']+$datos['{comprasND}']) - $datos['{comprasNC}'],2,1); //se resto la nota de credito
        
        /*$tabla['{totComp12}']=formato_numero($tabla['{totComp12}']+$datos['{compras12}'],2,1);
        $tabla['{totComp5}']=formato_numero($tabla['{totComp5}']+$datos['{compras5}'],2,1);
        $tabla['{totComp0}']=formato_numero($tabla['{totComp0}']+$datos['{compras0}'],2,1);
        $tabla['{totCompNC}']=formato_numero($tabla['{totCompNC}']+$datos['{comprasNC}'],2,1);
        $tabla['{totImport}']=formato_numero($tabla['{totImport}']+$datos['{importaciones}'],2,1);
        $tabla['{totCompIva}']=formato_numero($tabla['{totCompIva}']+$datos['{comprasIva}'],2,1);
        $tabla['{totCompras}']=formato_numero($tabla['{totCompras}']+$datos['{comprasTotal}'],2,1);*/
        
        $tabla['{totComp15}']=formato_numero($tabla['{totComp15}']+$datos['{compras15}'],2,1);
        $tabla['{totComp12}']=formato_numero($tabla['{totComp12}']+$datos['{compras12}'],2,1);
        $tabla['{totComp8}']=formato_numero($tabla['{totComp8}']+$datos['{compras8}'],2,1);
        $tabla['{totComp5}']=formato_numero($tabla['{totComp5}']+$datos['{compras5}'],2,1);
        $tabla['{totComp0}']=formato_numero($tabla['{totComp0}']+$datos['{compras0}'],2,1);
        $tabla['{totCompND}']=formato_numero($tabla['{totCompND}']+$datos['{comprasND}'],2,1);
        $tabla['{totCompNC}']=formato_numero($tabla['{totCompNC}']+$datos['{comprasNC}'],2,1);
        $tabla['{totImport}']=formato_numero($tabla['{totImport}']+$datos['{importaciones}'],2,1);
        $tabla['{totCompIva}']=formato_numero($tabla['{totCompIva}']+$datos['{comprasIva}'],2,1);
        $tabla['{totCompras}']=formato_numero($tabla['{totCompras}']+$datos['{comprasTotal}'],2,1);


        /* VENTAS */       
        //$datos['{401}']=formato_numero($vent_contado['Total']+$vent_credito['Total'],2,1);//VENTAS GRAVADAS TARIFA 12%
        //$datos['{411}']=formato_numero($datos['{401}']-$NC_Ventas['Total'],2,1);//VENTAS GRAVADAS TARIFA 12% N/C
        //$datos['{411}']=formato_numero($datos['{401}']-$NC_contado['Total']-$NC_credito['Total'],2,1);//VENTAS GRAVADAS TARIFA 12% N/C 
       

        //$datos['{421}']=formato_numero(($datos['{411}']*$valorIva)/100,2,1);

        
        //$datos['{402}']=formato_numero($row_402['Total'],2,1);//VENTAS ACTIVOS FIJOS GRAVADAS TARIFA 12%
       
       
                                                            //TOTAL
        //$datos['{412}']=formato_numero($row_402['Total']-$row_412['Total'],2,1);//VENTAS ACTIVOS FIJOS  GRAVADAS TARIFA 12% N/C

        //$datos['{422}']=formato_numero(($datos['{412}']*$valorIva)/100,2,1);
        
        //$datos['{403}']=formato_numero($row_403['Total'],2,1);//VENTAS GRAVADAS TARIFA 0%
        //$datos['{413}']=formato_numero($row_403['Total']-$row_413['Total'],2,1);//VENTAS GRAVADAS TARIFA 0% N/C          
         
        //$datos['{404}']=formato_numero($row_404['Total'],2,1);//VENTAS ACTIVOS FIJOS GRAVADAS TARIFA 0%
        //$datos['{414}']=formato_numero($row_404['Total']-$row_414['Total'],2,1);//VENTAS ACTIVOS FIJOS  GRAVADAS TARIFA 12% N/C 
        
        //INFORMACION RETENCIONES BANCARIAS EN VENTAS
        $datos['{retVentaInfo}']="[{\"mes\":'".mes($mes, 1)."'},{\"retVnt\":\"".formato_numero($row_Fue['Ret_Fue'],2,1)."\"},{\"retBco\":\"".formato_numero($row_Ret_Ban['Ret_Fue'],2,1)."\"},{'eti':'Renta'}]";
        $datos['{ivaVentaInfo}']="[{\"mes\":'".mes($mes, 1)."'},{\"retVnt\":\"".formato_numero($row_609['Iva_Ret'],2,1)."\"},{\"retBco\":\"".formato_numero($row_Ret_Ban['Ret_Iva'],2,1)."\"},{'eti':'Iva'}]";

        //INFORMACION NOTAS CREDITO/DEBITO VENTAS
        $datos['{ncInfo}']="[{\"mes\":'".mes($mes, 1)."'},{\"inf0\":\"".formato_numero($ventaIva['Total0NC'],2,1)."\"},{\"inf5\":\"".formato_numero($ventaIva['Total5NC'],2,1)."\"},{\"inf8\":\"".formato_numero($ventaIva['Total8NC'],2,1)."\"},{\"inf12\":\"".formato_numero($ventaIva['Total12NC'],2,1)."\"},{\"inf15\":\"".formato_numero($ventaIva['Total15NC'],2,1)."\"}]";
        $datos['{ndInfo}']="[{\"mes\":'".mes($mes, 1)."'},{\"inf0\":\"".formato_numero($ventaIva['Total0ND'],2,1)."\"},{\"inf5\":\"".formato_numero($ventaIva['Total5ND'],2,1)."\"},{\"inf8\":\"".formato_numero($ventaIva['Total8ND'],2,1)."\"},{\"inf12\":\"".formato_numero($ventaIva['Total12ND'],2,1)."\"},{\"inf15\":\"".formato_numero($ventaIva['Total15ND'],2,1)."\"}]";

        //INFORMACION NOTAS CREDITO/DEBITO COMPRAS
        $datos['{ncInfoc}']="[{\"mes\":'".mes($mes, 1)."'},{\"inf0\":\"".formato_numero($compras['subnc0'],2,1)."\"},{\"inf5\":\"".formato_numero($compras['subnc5'],2,1)."\"},{\"inf8\":\"".formato_numero($compras['subnc8'],2,1)."\"},{\"inf12\":\"".formato_numero($compras['subnc12'],2,1)."\"},{\"inf15\":\"".formato_numero($compras['subnc15'],2,1)."\"}]";
        $datos['{ndInfoc}']="[{\"mes\":'".mes($mes, 1)."'},{\"inf0\":\"".formato_numero($compras['subnd0'],2,1)."\"},{\"inf5\":\"".formato_numero($compras['subnd5'],2,1)."\"},{\"inf8\":\"".formato_numero($compras['subnd8'],2,1)."\"},{\"inf12\":\"".formato_numero($compras['subnd12'],2,1)."\"},{\"inf15\":\"".formato_numero($compras['subnd15'],2,1)."\"}]";

       // $tabla=$datos['{ncInfo}'];
        //TARIFA IVA           
        $datos['{ventas12}']= formato_numero($ventaIva['Total12'],2,1);//formato_numero($datos['{412}']+$datos['{411}'],2,1);        
        $datos['{ventas15}']= formato_numero($ventaIva['Total15'],2,1);
        $datos['{ventas8}']= formato_numero($ventaIva['Tota8'],2,1);
        $datos['{ventas5}']= formato_numero($ventaIva['Total5'],2,1);
        $datos['{ventas0}']=formato_numero($venta0['Total0'],2,1);//formato_numero($datos['{413}']+$datos['{414}'],2,1);
        $datos['{ventasND}']=formato_numero($ventaIva['TotalND']/*+$venta0['TotalND']*/,2,1);
        $datos['{ventasNC}']=formato_numero($ventaIva['TotalNC']/*+$venta0['TotalNC']*/,2,1);
        $datos['{ventasIva}']=formato_numero(($ventaIva['TotalIva']+$ventaIva['Iva5'])-($ventaIva['IvaNC']+$venta0['IvaNC']),2,1);//formato_numero($datos['{422}']+$datos['{421}'],2,1);
        $datos['{ventasTotal}']=formato_numero(($datos['{ventas12}']+$datos['{ventas15}']+$datos['{ventas5}']+$datos['{ventas0}'])-$datos['{ventasNC}'],2,1);
        
        $tabla['{totVent12}']=formato_numero($tabla['{totVent12}']+$datos['{ventas12}'],2,1);
        $tabla['{totVent15}']=formato_numero($tabla['{totVent15}']+$datos['{ventas15}'],2,1);
        $tabla['{totVent8}']=formato_numero($tabla['{totVent8}']+$datos['{ventas8}'],2,1);
        $tabla['{totVent5}']=formato_numero($tabla['{totVent5}']+$datos['{ventas5}'],2,1);
        $tabla['{totVent0}']=formato_numero($tabla['{totVent0}']+$datos['{ventas0}'],2,1);
        $tabla['{totVentND}']=formato_numero($tabla['{totVentND}']+$datos['{ventasND}'],2,1);
        $tabla['{totVentNC}']= formato_numero($tabla['{totVentNC}']+$datos['{ventasNC}'],2,1);//formato_numero($tabla['{totNC_Venta}']+$datos['{ventas0}'],2,1).'xxxx';
        $tabla['{totVentIva}']=formato_numero($tabla['{totVentIva}']+$datos['{ventasIva}'],2,1);        
        $tabla['{totVentas}']=formato_numero($tabla['{totVentas}']+$datos['{ventasTotal}'],2,1);
        
        $datos['{VetCompr}']=formato_numero($datos['{ventasTotal}']-$datos['{comprasTotal}'],2,1);
        
        $datos['{609}']=formato_numero(($row_609['Iva_Ret']*1+$row_Ret_Ban['Ret_Iva']),2,1);//Ret IVA Ventas
        $datos['{ven_fuen}']=formato_numero(($row_Fue['Ret_Fue']*1+$row_Ret_Ban['Ret_Fue']*1),2,1);//Ret Ventas
        $datos['{721}']=formato_numero($row_721['Valor'],2,1);//Ret del 10
        $datos['{723}']=formato_numero($row_723['Valor'],2,1);//Ret del 20
        $datos['{725}']=formato_numero($row_725['Valor'],2,1);//Ret del 30
        $datos['{727}']=formato_numero($row_727['Valor'],2,1);//Ret del 50
        $datos['{729}']=formato_numero($row_729['Valor'],2,1);//Ret del 70
        $datos['{731}']=formato_numero($row_731['Valor'],2,1);//Ret del 100
        $datos['{799}']=formato_numero(($datos['{721}']*1)+($datos['{723}']*1)+($datos['{725}']*1)+($datos['{727}'])+($datos['{729}']*1)+($datos['{731}']*1),2,1);
        
        $tabla['{tot609}']=formato_numero($tabla['{tot609}']+$datos['{609}'],2,1);
        $tabla['{totven_fuen}']=formato_numero($tabla['{totven_fuen}']+$datos['{ven_fuen}'],2,1);
        $tabla['{tot721}']=formato_numero($tabla['{tot721}']+$datos['{721}'],2,1);
        $tabla['{tot723}']=formato_numero($tabla['{tot723}']+$datos['{723}'],2,1);
        $tabla['{tot725}']=formato_numero($tabla['{tot725}']+$datos['{725}'],2,1);
        $tabla['{tot727}']=formato_numero($tabla['{tot727}']+$datos['{727}'],2,1);
        $tabla['{tot729}']=formato_numero($tabla['{tot729}']+$datos['{729}'],2,1);
        $tabla['{tot731}']=formato_numero($tabla['{tot731}']+$datos['{731}'],2,1);

        $tabla['{tot799}']= formato_numero($tabla['{tot799}']+$datos['{799}'],2,1);
        
        //var_dump($datos['{413}']);
        $tabla['{data}']= $tabla['{data}'].reporteArray($datos,$body);
    }    
    $tabla['{totalArray103}']=reporteArray($tabla,$body103);
    $responce['tabla']=reporteHtml($tabla,'tes_pri_con_trib.html');
    
    if($obBD_con1->Error==0) $responce['success']=true;
    else {$responce['success']=false; $responce['message']=$obBD_con1->MsgError;}    
    echo json_encode($responce);exit();
}

?>
<!DOCTYPE html>
<HTML>
	<HEAD>		
                <meta charset="UTF-8">
                <TITLE><?Php echo "Control Tributario [EXA]";//$Ses_Sys_Nom; ?></TITLE>
                <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php")?>

                <script type="text/ecmascript" src="../../framework/jquery/MonthPicker/jquery.mtz.monthpicker.min.js?X=2"></script>
                <style>                     
                    table.mtz-monthpicker{
                        border-collapse: separate;border-spacing: 4px 4px;
                    }
                    .mtz-monthpicker.mtz-monthpicker-month{
                        padding-top:5px;padding-bottom:5px;cursor:default;
                    }
                </style>
	</HEAD>
<BODY>

    <div class="panel panel-main">
        <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Reporte de Control Tributario</h3></div>
        
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <form id="formFiltro" class="form-inline">
              <fieldset class="exa-fieldset">                           
                           <legend class="Titulos2">Filtrado</legend> <!-- Form Name -->    
                <div class="form-group">
                  <label for="fromMonth"> Año:</label>
				  <?php $anios=$obBD_con1->getArrayConsulta(38,$Ses_Emp_Cod,$obBD_conexion); $annio=date("Y"); ?>
                  <select name="anio" id="anio" onchange="$.createMonthRange('#From','#To',this.value);" class="form-control input-sm">
						<?Php	//Presentamos los dos ultimos a�os para generar el XML  
                        foreach($anios AS $row){ ?>
							<option <?Php if($annio==$row['Periodo']){ echo "selected";}?> value="<?Php echo $row['Periodo']; ?>"><?Php echo $row['Periodo']; ?></option>						
						<?Php }  ?>
                  </select>
                </div>
                <div class="form-group"></div>
                <div class="form-group">
                  <label for="From">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Mes Inicio:</label>
                  <div class="input-group input-group-sm">                    
                    <span id="FromMes" class="form-control normal" style="width:125px;"></span>
                    <input id="From" name="From" type="hidden" data-monthplacer="#FromMes" class="mtz-monthpicker-widgetcontainer" value="2017-01">
                    <span class="input-group-btn">
                        <button onclick="$('#From').monthpicker('show','#FromMes');" class="btn btn-success" type="button"><span class="glyphicon glyphicon-calendar" title="Seleccione Mes"></span></button>
                    </span> 
                  </div>
                  <!--<input id="From" name="From" type="text" class="form-control" value='<?php echo date("Y"); ?>-01' >-->
                </div>
                <div class="form-group">
                  <label for="To">&nbsp;&nbsp;&nbsp;&nbsp;Mes Fin:</label>
                  <div class="input-group input-group-sm">                    
                    <span id="ToMes" class="form-control normal" style="width:125px;"></span>
                    <input id="To" name="To" type="hidden" data-monthplacer="#ToMes" class="mtz-monthpicker-widgetcontainer" value="2017-12">
                    <span class="input-group-btn">
                        <button onclick="$('#To').monthpicker('show','#ToMes');" class="btn btn-success" type="button"><span class="glyphicon glyphicon-calendar" title="Seleccione Mes"></span></button>
                    </span>
                  </div>
                  <!--<input id="To" name="To" type="text" class="form-control input-sm" value='<?php echo date("Y"); ?>-12' >-->
                </div>&nbsp;&nbsp;&nbsp;&nbsp;                
                <button type="button" class="btn btn-primary btn-sm" onclick="previewReport()">Vista Previa</button>
              </fieldset> 
            </form>
            <div class="row">
                <div class="col-xs-12">
                    <div id="ver" style="min-height: 200px;">
                        
                    </div>
                    <button id="btnExport" style="margin-top: 10px" class="btn btn-success btn-sm" onclick="$.downloadFile($('#tableControl').exportarExcelBlob('Hoja 1'),'ControlTrib-'+$.getDate()+'.xls');" type="button">
                        Exportar a Excel <i class="glyphicon glyphicon-download"></i>
                    </button>
                </div>
                
            </div>
        </div>
    </div>   

    <div id="verDetalle" title="Detalle" style="display: none">
		<div class="row">
			<div class="col-sm-12">
				<fieldset class="exa-fieldset">
				<legend class="Titulos2">Detalle de subtotales</legend>
					<form id="verPagosForm" class="form-horizontal normal">
						<div class="row">
							<div class="col-sm-12">								
									<table id="subtoral" border="1px">                                        
                                        <tr style="background-color: #95AEBA; font-weight: bold;">
                                            <td width="20%" align="center">MES</td>
                                            <td width="10%" align="center">Tarifa 0%&nbsp;</td>
                                            <td width="10%" align="center">Tarifa 5%&nbsp;</td>
                                            <td width="10%" align="center">Tarifa 8%&nbsp;</td>
                                            <td width="10%" align="center">Tarifa 12%&nbsp;</td>
                                            <td width="10%" align="center">Tarifa 15%&nbsp;</td>                                                
                                        </tr>                                                   
                                        <tr>
                                            <td style="font-size: 14px"><b><labe id='infm'></label>&nbsp;</b></td>    
                                            <td  align="center"><labe id='inf0'></label>&nbsp;</td>    
                                            <td  align="center"><labe id='inf5'></label>&nbsp;</td>    
                                            <td  align="center"><labe id='inf8'></label>&nbsp;</td>    
                                            <td  align="center"><labe id='inf12'></label>&nbsp;</td>    
                                            <td  align="center"><labe id='inf15'></label>&nbsp;</td>    
                                        </tr>                                           
                                    </table>	
                                    <table id="retencion" border="1px">                                        
                                        <tr style="background-color: #95AEBA; font-weight: bold;">
                                            <td width="20%" align="center">MES</td>
                                            <td width="10%" align="center">Ret. Bancos</td>
                                            <td width="10%" align="center">Ret. Ventas</td>                                            
                                            <!--<td width="10%" align="center">Total</td>-->                                                
                                        </tr>                                                   
                                        <tr>
                                            <td style="font-size: 14px"><b><labe id='infRetm'></label>&nbsp;</b></td>    
                                            <td  align="center"><labe id='retBco'></label>&nbsp;</td>    
                                            <td  align="center"><labe id='retVnt'></label>&nbsp;</td>    
                                            <!--<td  align="center"><labe id='retTot'></label>&nbsp;</td>-->
                                            
                                        </tr>                                           
                                    </table>	
                                    <br><br>													
							</div>							
						</div>
					</form>
				</fieldset>
			</div>
		</div>
    </div> 

   <script type="text/javascript">    
        $("#verDetalle").createDialog({ width: 500, height: 200, icon: 'info-sign' });    
       $( document ).ready(function() {
            $.createMonthRange('#From','#To',<?php echo date("Y"); ?>,{openOnFocus:false});
            $('#btnExport').hide();
       });  
       function mostrarDetalle(o,t){            
            if(t=='sub'){
                $('#subtoral').show();
                $('#retencion').hide();
                $('#infm').html(o[0].mes);
                $('#inf0').html(o[1].inf0);
                $('#inf5').html(o[2].inf5);
                $('#inf8').html(o[3].inf8);
                $('#inf12').html(o[4].inf12);
                $('#inf15').html(o[5].inf15);
            }
            if(t=='ret'){
                $('#retencion').show();
                $('#subtoral').hide();                
                $('#infRetm').html(o[0].mes+' - '+ o[3].eti);
                $('#retVnt').html(o[1].retVnt);
                $('#retBco').html(o[2].retBco);
                //$('#retTot').html((o[1].retVnt*1)+(o[2].retBco*1));                
            }
            $("#verDetalle").dialog('open');
       }
       function previewReport(){ 
           var data=$('#formFiltro').getData('save');
           data['fromMonth']=$('#From').monthpicker('getMonth');
           data['toMonth']=$('#To').monthpicker('getMonth');
           $("#loader").show();
           $.post("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",data, function(response){	
                    if(response['success']===true){
                       $('#ver').html(response['tabla']);
                       $('#btnExport').show();
                    }else{$.alert("No se logro cargar los datos!");}
            },'json').fail(function(error) {$.alert("El Servidor ha fallado en responder!");}).always(function(){ $("#loader").fadeOut("slow"); });          
       }
   </script>

     

   <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>
</HTML>