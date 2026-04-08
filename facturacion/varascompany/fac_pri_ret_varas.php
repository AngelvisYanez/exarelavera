<?php include('../IMPRIMIR/datos_retenc.php') ?>
<html>
    <head>
        <title><?Php echo $Ses_Sys_Nom; ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">    
        <style type="text/css">   
             body{width:830px;padding:0;margin:0;}
            .flota{position: absolute;font-size: 12px;font-weight: normal;font: 12pt Arial, Helvetica, sans-serif;}
            .flota.truncate{white-space:nowrap;overflow:hidden;font-stretch:condensed;line-height: 16px;}
            .flota.number{text-align:right;}
            .detalle{position: absolute;font-size: 12px;font-weight: normal;font: 9pt Verdana, Geneva, sans-serif;}            
        </style>
    </head>
<body>
    <?Php  list($anio, $mes, $dia) = split('[/.-]', $row_prin_renta['Ret_Fec']);?>
    <!--fecha  --><span style="<?php echo getCss('fecha'); ?>" class="flota"><? echo $dia.'/'.$mes.'/'.$anio; ?></span>
    <!--provee--><span style="<?php echo getCss('proveedor'); ?>" class="flota truncate"><? echo $row_prin_renta['Prs_Ape'].' '.$row_prin_renta['Prs_Nom']; ?></span>
    <!--direcci--><span style="<?php echo getCss('direccion'); ?>" class="flota truncate"><? echo $row_prin_renta['Prs_Dir']; ?></span>
    <!--ci/ruc --><span style="<?php echo getCss('cedu_ruc'); ?>" class="flota"><? echo $row_prin_renta['Prs_Ced']; ?></span>
    <!--ciudad --><!--<span style="<?php echo getCss('ciudad'); ?>" class="flota truncate"><? //echo $row_prin_renta['Ciu_Des']; ?></span>-->
    
    <!--documento--><span style="<?php echo getCss('docu'); ?>" class="flota truncate"><? echo $row_prin_renta['Tic_Des']; ?></span>
    <!--docu_nume--><span style="<?php echo getCss('docu_num'); ?>" class="flota"><? echo $row_prin_renta['Cop_Num']; ?></span>
    <!--docu_fech--><!--<span style="<?php echo getCss('docu_fech'); ?>" class="flota"><? echo $row_prin_renta['Cop_Fec']; ?></span>-->
    <!--docu_auto--><!--<span style="<?php echo getCss('docu_auto'); ?>" class="flota"><? echo $row_prin_renta['Cop_Aut']; ?></span>-->
    <!--docu_cadu--><!--<span style="<?php echo getCss('docu_cadu'); ?>" class="flota"><? echo $row_prin_renta['Cop_Cad']; ?></span>-->
    
    <? 
    $aux=getCss('item','y');
    $Total_Ret=0; $Eje_Fis=explode('-',$row_prin_renta['Ret_Fec']); $Ejerci=$Eje_Fis[0];
    foreach($rs_prin_renta as $row){ ?>
        <span style="<? echo "top:{$aux}px;left:".getCss('item_ejer','x')."px;width:".getCss('item_cant','width')."px;" ?>" class="flota number"><?php echo $Ejerci;?></span>
        <span style="<? echo "top:{$aux}px;left:".getCss('item_codi','x')."px;width:".getCss('item_codi','width')."px;" ?>" class="flota truncate"><?php echo $row['Ren_Sri'].'-'.$row['Ret_Imp']?></span>
        <span style="<? echo "top:{$aux}px;left:".getCss('item_base','x')."px;width:".getCss('item_base','width')."px;" ?>" class="flota number"><?php echo number_format($row['Ret_Bas'], 2,'.',','); ?></span>
        <span style="<? echo "top:{$aux}px;left:".getCss('item_porc','x')."px;width:".getCss('item_porc','width')."px;" ?>" class="flota number"><?php echo $row['Ren_Por'].'%'; ?></span>
        <span style="<? echo "top:{$aux}px;left:".getCss('item_rete','x')."px;width:".getCss('item_rete','width')."px;" ?>" class="flota number"><?php  echo $Val_Ret=formato_numero((formato_numero($row['Ret_Bas'],2,1)* $row['Ren_Por'])/100,2,1); $Total_Ret+=$Val_Ret; ?></span>
    <? unset($Ejerci); $aux+=20; } ?>    
    
    <!--Total--><span style="<?php echo getCss('total'); ?>text-align:right;" class="flota"><strong><?Php echo number_format ($Total_Ret, 2,'.',''); ?></strong></span>

</body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>