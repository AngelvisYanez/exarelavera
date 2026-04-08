<?	
/*
* Descripción: Reporte de la opción Totales de documentos de compra
* Fecha de actualización: 2012-09-16
* Desarrollador: Lewis Chimarro
*/	
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_compras.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');		

/**
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Comt($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Comt; 	  
/**
* Inicializa la variable op cuando no esta seteada la misma
*/


if (!(isset($op)))
	$op = 1; 	   

/**
* Consulta el tipo de comprobante 
*/
$rs_tip_compr = $obBD_con1->getArrayConsulta(729, '', $obBD_conexion);
/**
* Consulta el sustento 
*/
$rs_sustento = $obBD_con1->getArrayConsulta(711, '', $obBD_conexion);	
?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
	<?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<link href="../../mascaras/model1/estilos/print.css" rel="stylesheet" type="text/css">
	</HEAD>
<BODY>
     <table width="100%" border="0" align="center">	
     <tr align="center">
	   <td  valign="top" align="center"><?php
		   if (($optest) == "A")
		   {
				$estado = 'Activas'; 
		   } else 
		   {
				$estado = 'Anuladas';
		   }//Fin del if (($optest) == "A")
		$titulo = "<strong><span class='TITULO_REPORTE_2'>Reporte de Documentos de Compras $estado</span></strong>";
		$subtitulo = "<strong><span class='TITULO_REPORTE'>Desde el ".$txt_fec_ini." Hasta el ".$txt_fec_fin." </span></strong>";
		 $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod, $titulo, $subtitulo, $obBD_conexion); ?></td>
	   </tr>
	 <tr>
        <td valign="top"> 
<table width="100%" border="1" cellpadding="0" cellspacing="0" class="TablaRepCompr">
  <thead>
    <?php 
    if(!isset($sustento))
    { 
	  /** 
	  * Busqueda de facturas en un rango de fechas  
	  */
	  $rs_buscar = $obBD_con1->getArrayConsulta(537, $txt_fec_ini.'*'.$txt_fec_fin.'*'.$Tic_Cod.'*'.$optest.'*'.$Ses_Emp_Cod, $obBD_conexion); 
    }//Fin del if(!isset($sustento))
    
    /**
    * Prepara la cadena concatenando los tipos de documentos para enviarlos como parametro de la sql
    */
    if($Tic_Cod=='T')
    {	  
     foreach($rs_tip_compr as $row_rs_tip_compr)
     {  
        $par_sql=$par_sql.'compras.Tic_Cod='.$row_rs_tip_compr['Tic_Cod'].'  OR ';
     } 
     $par_sql=substr($par_sql,0,strlen($par_sql)-4);
     $par_sql='('.$par_sql.')';
    }
    else
    {
        $par_sql='compras.Tic_Cod='.$Tic_Cod;
    }
    
    /**
    * Prepara la cadena concatenando los sustentos tributarios para enviarlos como parametro de la sql
    */	
    if ($Tri_Cod !='T')
    {		
        $sustento_cod[]=$Tri_Cod;
    }//fin del if $Tri_Cod; 
    else
    { 
        foreach($rs_sustento as $row_rs_sustento)
        { 						
            $sustento_cod[]= $row_rs_sustento['Tri_Cod']; 			
        }
    }//fin del else
    
    /**
    * Buscar las compras cuando NO se ha seleccionado "Compras no sujetas a retenci&oacute;n"
    */	
    if(empty($Chk_Ret))
    {
        /**
        * Contador del boton mas + menos -
        */
        $i=0; 
        $fila = 0;
        $max_adq=array();
        $max_com=array();
         /**
         * Consulto las adquisiciones del rango de fechas 
         */
        $rs_adquisio = $obBD_con1->getArrayConsulta(324, $txt_fec_ini.'*'.$txt_fec_fin.'*'.$par_sql.'*'.$optest.'*'.$Ses_Emp_Cod, $obBD_conexion);
        $maximo=count($rs_adquisio);
        
        /**
        * Recorrido de los tipos de sustentos tributarios 
        */
        for ($x=0; $x<=count($sustento_cod)-1; $x++)
        {		
            if($op_busqueda=='A')
			{
				/*Por Fechas*/	
				$paramFecCi=" AND Cop_Fec BETWEEN '".$txt_fec_ini."' AND '".$txt_fec_fin."' ";
				$paramFecCi=$paramFecCi." AND Prs_Ced='".$txtRuc."' ";
			}
			if($op_busqueda=='F')
			{
				$paramFecCi=" AND Cop_Fec BETWEEN '".$txt_fec_ini."' AND '".$txt_fec_fin."' ";
			}
			if($op_busqueda=='C')
			{    
				/*Por Ruc*/
				$paramFecCi=" AND Prs_Ced='".$txtRuc."'";
			}
			
			/**
            * Consultar las facturas de compras en base a la fecha de inicio, fin, tipo de comprobante, estado y sustento tributario 
            */
            $rs_buscar = $obBD_con1->getArrayConsulta(1099, $paramFecCi.'*'.$par_sql.'*'.$optest.'*'.$sustento_cod[$x].'*'.$Ses_Emp_Cod, $obBD_conexion); 
            $row = current($rs_buscar);
    
            if (count($rs_buscar) > 0)	
            { 
                $contar_resultado=$contar_resultado+count($rs_buscar);
                /**
                * Variable columnas el maximo numero de tipos de aquisiciones 
                * por compra multiplicado por la frecuencia 2 
                */
                $columnas=$maximo*2;
                /**
                * Sumo en la variable columnas el numero de colspan=13 
                */
                $columnas=$columnas+14;
             ?>
    <tr class="Texto_Reporte">
      <?Php 
              if(count($rs_adquisio) > 0)
              { 
                  ?>
      <th colspan="10">&nbsp;</th>
      <?Php
                  /**
                  * Recorrido de las facturas de compra  
                  */
                  foreach($rs_adquisio as $row_rs_adquisio)
                  { ?>
      					<th colspan="2" align="center" class="TablaRepComprLeft"><?php echo $row_rs_adquisio['Adq_Des'];  ?></th>
      <?Php 	 
                  } 
				  ?>
     					<th colspan="2" class="TablaRepComprLeft">&nbsp;</th>
      <?Php
              }//if(count($rs_adquisio) > 0)
              else
              { ?>
      					<th width="1" colspan="<?Php echo $columnas+11; ?>">&nbsp;</th>
      <?Php 
               } ?>
    </tr>
    <tr class="Texto_Reporte">
      <th width="31" align="center" class="TablaRepCompr">Item</th>
      <th width="33" align="center" class="TablaRepCompr">C&oacute;d. Int. </th>
      <th width="118" align="center" class="TablaRepCompr">Tip. Doc.</th>
      <th width="68" align="center" class="TablaRepCompr">Nro. Doc.</th>
      <th width="106" align="center" class="TablaRepCompr">Autorizaci&oacute;n</th>
      <th width="75" align="center" class="TablaRepCompr">Fecha </th>
      <th width="89" align="center" class="TablaRepCompr">C&eacute;dula/R.U.C</th>
      <th colspan="3" align="center" class="TablaRepCompr">Proveedor</th>
      <?Php
		  /**
		  * Despliega el n&uacute;mero de columnas de tipos de adquisiciones  
		  */
		  for($j=0; $j<count($rs_adquisio); $j++)
		  { ?>
			<th width="60" align="center" class="TablaRepCompr">BASE 0%</th>
			<th width="60" align="center" class="TablaRepCompr">BASE IVA</th>
	   <?Php 
		  } ?>
      <th width="50" align="center" class="TablaRepCompr">IVA</th>
      <th width="50" align="center" class="TablaRepCompr">TOTAL</th>
      </tr>
  </thead>
  <tbody>
    <?Php
                /***
                * consultar el iva activo 
                * "Esto deber&aacute; cambiarse a cargar el Iva en base a la compra"
                */		
                $rs_iva_com = $obBD_con1->getArrayConsulta(727, $row_rs_buscar['Cop_Cod'], $obBD_conexion); 
                $xiv=0;
                foreach($rs_iva_com as $row_rs_iva_com)
                {
                    $iva_codigo[$xiv]=$row_rs_iva_com['Iva_Cod'];
                    $iva_porcentaje[$xiv]=$row_rs_iva_com['Iva_Por'];
                    $xiv++;
                }
            
                /**
                * Inicializo el contador en 0 
                */
                $acumtotal=0;
                foreach($rs_buscar as $row_rs_buscar)
                { 
                    $acumtotal=$acumtotal+count($rs_buscar);
                    $i++;
                    $fila++;
                    if($row_rs_buscar['Cop_Est']=='I')
                      { $rojo='#FF0000'; $anulada++; }else{$rojo='';}		
                    ?>
    <tr class="Texto_Reporte">
      <td align="center"><FONT COLOR="<? echo $rojo;?>"><?php echo $fila; ?></FONT></td>
      <td align="center"><FONT COLOR="<? echo $rojo;?>">
        <?php $Cop_Cod=$row_rs_buscar['Cop_Cod']; echo $Cop_Cod; ?>
        </FONT></td>
      <td align="center"><FONT COLOR="<? echo $rojo;?>"><?PHP echo $row_rs_buscar['Tic_Des']; ?></FONT></td>
      <td align="center"><FONT COLOR="<? echo $rojo;?>">
        <?php $Num_Fac=$row_rs_buscar['Cop_Num']; echo $Num_Fac; ?>
        </FONT></td>
      <td align="center"><FONT COLOR="<? echo $rojo;?>">
        <?php $Cop_Aut=$row_rs_buscar['Cop_Aut'];  echo substr($Cop_Aut,0,18)."<br>".substr($Cop_Aut,18,strlen($Cop_Aut)); ?>
        </FONT></td>
      <td align="center"><FONT COLOR="<? echo $rojo;?>">
        <?php $Fec_Com=$row_rs_buscar['Cop_Fec'];  echo $Fec_Com; ?>
        </FONT></td>
      <td align="center"><FONT COLOR="<? echo $rojo;?>">
        <?PHP $Prs_Ced= $row_rs_buscar['Prs_Ced']; echo $Prs_Ced; ?>
        </FONT></td>
      <td colspan="3" align="center"><FONT COLOR="<? echo $rojo;?>"><?PHP echo $row_rs_buscar['Prs_Ape'].'&nbsp'.$row_rs_buscar['Prs_Nom']; ?></FONT></td>
      <?php 			
                    if(count($rs_adquisio) > 0)
                    { 
                        /** 
                        * Inicio un contador en cero xc 
                        */
                        $xc=0;
                        /** 
                        * inicializo el contador del iva en 0 
                        */
                        $xiv=0;		
                        
                        foreach($rs_adquisio as $row_rs_adquisio)
                        {
                            /**
                            * $iva_codigo[0] representa el %0
                            */
                        ?>
      <td align="right"><FONT COLOR="<? echo $rojo;?>">
        <?php  
                        $row_importe_comp = $obBD_con1->getRowConsulta(323, $row_rs_buscar['Cop_Cod'].'*'.$row_rs_adquisio['Adq_Cod'].'*'.$iva_codigo[0], $obBD_conexion); 		
                       if ($row_importe_comp['Iva_Por']==0)
                       {  
                            if($row_importe_comp['Importe']>0)
                            {
                                echo formato_numero($row_importe_comp['Importe']-($row_importe_comp['Importe']*$row_importe_comp['Cop_Des']/100),2,1);
                                /**
                                * Acumulador para las compras con base cero (0) 
                                */
                                 $sum_cero[$xc]=$sum_cero[$xc]+round($row_importe_comp['Importe']-($row_importe_comp['Importe']*$row_importe_comp['Cop_Des']/100),2);
                            }
                            else
                            {
                                $sum_cero[$xc]=$sum_cero[$xc]+0;
                            }			  
                        }
                        else  
                        {  
                          /**
                          * Acumulador para las compras con base cero (0) 
                          */
                          $sum_cero[$xc]=$sum_cero[$xc]+0;
                       } 	
                        /**
                        * $iva_codigo[1] representa el %12
                        */
                        ?>
      </FONT></td>
      <td align="right"><FONT COLOR="<? echo $rojo;?>">
        <?php	
                        $row_importe_comp = $obBD_con1->getRowConsulta(323, $row_rs_buscar['Cop_Cod'].'*'.$row_rs_adquisio['Adq_Cod'].'*'.$iva_codigo[1], $obBD_conexion); 
                         if ($row_importe_comp['Iva_Por']!=0)
                          { 		   		 	  
                              if($row_importe_comp['Importe']>0)
                              {			  
                                  echo formato_numero($row_importe_comp['Importe']-($row_importe_comp['Importe']*$row_importe_comp['Cop_Des']/100),2,1);
                                  /**
                                  * Acumulador para las compras con base cero (0) 
                                  */
                                  $sum_base[$xc]=$sum_base[$xc]+round($row_importe_comp['Importe']-($row_importe_comp['Importe']*$row_importe_comp['Cop_Des']/100),2);
                              }
                              else
                              {
                                  $sum_base[$xc]=$sum_base[$xc]+0;
                              }
                           }
                           else  
                           {  
                              /**
                              * Acumulador para las compras con base cero (0) 
                              */
                              $sum_base[$xc]=$sum_base[$xc]+0;
                           }
                          ?>
      </FONT></td>
      <?Php
                            $xiv++;
                            $xc++;		
                    }//Fin del if(count($rs_adquisio) > 0)
                }//Fin del foreach($rs_buscar as $row_rs_buscar)
    
              $iva_factura=0;
                ?>
      <td align="right"><FONT COLOR="<?php echo $rojo;?>">
        <?php	
                /**
                * Retorno los calculos de las facturas 
                */
                $resultados = explode('*', $obBD_con1->calculosCompraIce($Cop_Cod, $obBD_conexion));
                if($resultados[3]>0)
                { 
                    $iva_factura=formato_numero($resultados[3],2,1);
                    echo $iva_factura; 
                    /**
                    * Acumulo en iva_tot el total de las facturas de compras 
                    */		
                    $iva_tot=$iva_tot+$resultados[3];  	
                }
                else 
                { 
                    echo "&nbsp"; 
                } ?>
        </FONT></td>
      <td align="right"><FONT COLOR="<? echo $rojo;?>"><?php echo $resultados[5];
                        /**
                        * Acumulo en tot_fac el total de las facturas de compras 
                        */		
                        $tot_fac=$tot_fac+$resultados[5];			   
                        ?></FONT></td>
      </tr>
    <?Php 
            }//Fin del count($rs_buscar) 
			?>
    <tr class="Texto_Reporte">
      <td colspan="10"><div align="right"><strong>SUBTOTAL POR TIPO DE COMPROBANTE :</strong></div></td>
      <?Php 
          for($j=0; $j< count($rs_adquisio); $j++)
          { ?>
      <td align="right"><strong><?Php 
                if($sum_cero[$j]>0)
                { 
                    echo formato_numero($sum_cero[$j],2,1);  
                    $sum_cero_total[$j]=$sum_cero_total[$j]+$sum_cero[$j];
                    $sum_cero[$j]=0; 
                }
                else
                { 
                    $sum_cero_total[$j]=$sum_cero_total[$j]+0;
                    echo "&nbsp"; 
                } 
          ?></strong></td>
      <td align="right"><?Php 
                if($sum_base[$j]>0)
                { 	  
                  echo formato_numero($sum_base[$j],2,1);  
                  $sum_base_total[$j]=$sum_base_total[$j]+$sum_base[$j];
                  $sum_base[$j]=0;  	  
                }
                else
                { 
                  $sum_base_total[$j]=$sum_base_total[$j]+0;
                  echo "&nbsp"; 	  	  
                }  ?></td>
      <?Php } ?>
      <td align="right"><strong><?Php 
                if($iva_tot>0)
                {
                    echo formato_numero($iva_tot,2,1);  
                    $iva_total_factura=$iva_total_factura+$iva_tot; $iva_tot=0; 
                } 
                else 
                { 
                    echo "&nbsp";  
                } ?></strong></td>
      <td align="right"><strong><?Php 
                if($tot_fac>0)
                { 
                    echo formato_numero($tot_fac,2,1); 
                    $total_facturas=$total_facturas+$tot_fac;  
                    $tot_fac=0; 
                }
                else
                { 
                    echo "&nbsp"; 
                } ?></strong></td>
      </tr>
    <?Php 		
         }//fin for ($x=0; $x<=count($sustento_cod)-1; $x++)
         
         if (isset($sustento) && count($rs_buscar)==0)
         { 
              $colsp3=$colsp1+15; ?>
    <?php 
         }	 
   	}
		
		if($contar_resultado>0)
		{ 
		/**
		* Inicio el if($contar_resultado>0)  
		*/ ?>
    <tr class="Texto_Reporte">
      <td colspan="10" align="right"><strong>TOTAL GENERAL:</strong></td>
      <?Php 
		  for($j=0; $j<count($rs_adquisio); $j++)
		  { ?>
      <td  align="right"><strong><?Php echo formato_numero($sum_cero_total[$j],2,1); ?></strong></td>
      <td  align="right"><strong><?Php echo formato_numero($sum_base_total[$j],2,1); ?></strong></td>
      <?Php 
	  	  }//for($j=0; $j<count($rs_adquisio); $j++)  ?>
      <td align="right"><strong><?Php echo formato_numero($iva_total_factura,2,1); ?></strong></td>
      <td align="right"><strong><?Php echo formato_numero($total_facturas,2,1); ?></strong></td>
      </tr>
    <?Php
		 }/* fin inicio el if(contar_resultado>0) */ 
}//fin del if(empty($Chk_Ret))	
else
{ 
/**
* C o m p r a s    n o     s u j e t a s    a    r e t e n c i &oacute; n
*/
	/**
	* Defino un contador 
	*/
	$contador_acumulado_2=0;
	/**
	* Recorrido de los tipos de sustentos tributarios 
	*/
	for ($x=0; $x<=count($sustento_cod)-1; $x++)
	{				
	/**
	* Consultar las facturas de compras en base a la fecha de inicio, fin, tipo de comprobante, estado y sustento tributario 
	*/
	$rs_buscar = $obBD_con1->getArrayConsulta(326, $txt_fec_ini.'*'.$txt_fec_fin.'*'.$par_sql.'*'.$optest.'*'.$sustento_cod[$x].'*'.$Ses_Emp_Cod, $obBD_conexion);
	$row = current($rs_buscar);
	$contador_acumulado_2=$contador_acumulado_2+count($rs_buscar);
	
		if(count($rs_buscar)>0)
		{
	 ?>
    <tr class="Texto_Reporte">
      <td colspan="14"><br>
        <h3 align="center"> <?Php echo $row['Tri_Des']; ?></h3></td>
    </tr>
    <tr class="Texto_Reporte">
      <td width="31" align="center"><strong>Item</strong></td>
      <td width="33" align="center"><strong>C&oacute;d. Int.</strong></td>
      <td width="118" align="center"><strong>Tip. Doc.</strong></td>
      <td width="68" align="center"><strong>Nro. Doc.</strong></td>
      <td width="106" align="center"><strong>Autorizaci&oacute;n</strong></td>
      <td width="75" align="center"><strong>Fecha </strong></td>
      <td width="89" align="center"><strong>C&eacute;dula/R.U.C</strong></td>
      <td width="143" align="center"><strong>Proveedor</strong></td>
      <td width="121" align="center"><strong>Importe</strong></td>
      <td width="19" align="center"><strong>IVA</strong></td>
      <td colspan="4" align="center"><strong>TOTAL</strong></td>
      </tr>
    <?Php
			$total_fac=0;
			$total_iva=0;
			$total_importe=0;
			
			foreach($rs_buscar as $row_rs_buscar)
			{
			$i++;
			$fila++;
			if($row_rs_buscar['Cop_Est']=='I')
			  { $rojo='#FF0000'; $anulada++; }else{$rojo='';}		
			?>
    <tr class="Texto_Reporte">
      <td align="center"><FONT COLOR="<? echo $rojo;?>"><?php echo $fila; ?></FONT></td>
      <td align="center"><FONT COLOR="<? echo $rojo;?>">
        <?php $Cop_Cod=$row_rs_buscar['Cop_Cod']; echo $Cop_Cod; ?>
        </FONT></td>
      <td align="center"><FONT COLOR="<? echo $rojo;?>"><?PHP echo $row_rs_buscar['Tic_Des']; ?></FONT></td>
      <td align="center"><FONT COLOR="<? echo $rojo;?>">
        <?php $Num_Fac=$row_rs_buscar['Cop_Num']; echo $Num_Fac; ?>
        </FONT></td>
      <td align="center"><FONT COLOR="<? echo $rojo;?>">
        <?php $Cop_Aut=$row_rs_buscar['Cop_Aut'];  echo $Cop_Aut; ?>
        </FONT></td>
      <td align="center"><FONT COLOR="<? echo $rojo;?>">
        <?php $Fec_Com=$row_rs_buscar['Cop_Fec'];  echo $Fec_Com; ?>
        </FONT></td>
      <td align="center"><FONT COLOR="<? echo $rojo;?>">
        <?PHP $Prs_Ced= $row_rs_buscar['Prs_Ced']; echo $Prs_Ced; ?>
        </FONT></td>
      <td  align="center"><FONT COLOR="<? echo $rojo;?>"><?PHP echo $row_rs_buscar['Prs_Ape'].' '.$row_rs_buscar['Prs_Nom']; ?></FONT></td>
      <td align="right"><FONT COLOR="<? echo $rojo;?>">
        <?php  $resultados = explode('*', $obBD_con1->calculosCompraIce($Cop_Cod, $obBD_conexion));
					$total_importe = $total_importe +  round($row_rs_buscar['Importe']-($row_rs_buscar['Importe']*$row_rs_buscar['Cop_Des']/100),2);
					echo formato_numero($row_rs_buscar['Importe']-($row_rs_buscar['Importe']*$row_rs_buscar['Cop_Des']/100),2,1);			 	  
			  ?>
        </FONT></td>
      <td align="right"><FONT COLOR="<? echo $rojo;?>">
        <?Php
				   $iva_factura=formato_numero($resultados[3],2,1);
				   //Total del iva
				   $total_iva = $total_iva + round($resultados[3],2);
				echo $iva_factura; ?>
        </FONT></td>
      <td colspan="4" align="right"><FONT COLOR="<? echo $rojo;?>">
        <?php
					echo $resultados[5];
					/**
					* Acumulo en tot_fac el total de las facturas de compras 
					*/		
					$total_fac=$total_fac+$iva_factura+($row_rs_buscar['Importe']-($row_rs_buscar['Importe']*$row_rs_buscar['Cop_Des']/100));				   
					?>
      </FONT></td>
      </tr>
    <?Php }//Fin del foreach $row_rs_buscar ?>
    <tr class="Texto_Reporte">
      <td colspan="8" ><strong><div align="right">SUBTOTAL POR TIPO DE COMPROBANTE :</div></strong></td>
      <td align="right"><strong><?php echo formato_numero($total_importe,2,1); ?></strong></td>
      <td align="right"><strong><?php echo  formato_numero($total_iva,2,1); ?></strong></td>
      <td colspan="4" align="right" ><strong>
        <?Php 
			/**
			* Total acumulado 
			*/
			$total_acumulado=$total_acumulado+$total_fac;
			$acumulado_iva = $acumulado_iva + $total_iva;
			$acumulado_importe = $acumulado_importe + $total_importe;
			echo formato_numero($total_fac,2,1); ?>
      </strong></td>
      </tr>
    <?Php  
		}
		  /**
		  * Almaceno el total acumulado 
		  */
		  $tot_acumulado=$tot_acumulado+$tot_fac;
    } /* Fin si !existe Chk_Ret*/ 
	//if($contador_acumulado_2!=0 ){

	if (round($total_acumulado)>0)
	{
	?>
    <tr class="Texto_Reporte">
      <td colspan="8" align="right" ><strong>TOTAL GENERAL:</strong></td>
      <td align="right"><strong><?Php echo formato_numero($acumulado_importe,2,1); ?></strong></td>
      <td align="right"><strong><?Php echo formato_numero($acumulado_iva,2,1); ?></strong></td>
      <td colspan="4" align="right" ><strong><?Php echo formato_numero($total_acumulado,2,1); ?></strong></td>
      </tr>
    <?Php
	}
} ?>
  </tbody>
</table>
  <br>
	</td>
  </tr>
	 <tr>
	   <td><div align="center"><?Php $obBD_con1->pieReporteStandar($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?></div></td>
	   </tr>
</table>	  
</BODY></HTML>
<?php 
/**
* Cierra conexiones de la base de datos 
*/
$obBD_conexion->cerrar();	
$obBD_con1->liberar();
?>