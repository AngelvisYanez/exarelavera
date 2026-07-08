<?php include('../IMPRIMIR/datos_retenc.php') ?>
<html>
    <head>
        <title><?Php echo $Ses_Sys_Nom; ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">    
        <style type="text/css">   
             body{width:830px;padding:0;margin:0;}
            .flota{position: absolute;font-size: 12px;font-weight: normal;font: 11pt Arial, Helvetica, sans-serif;}
            .flota.truncate{white-space:nowrap;overflow:hidden;font-stretch:condensed;line-height: 16px;}
            .flota.number{text-align:right;}
            .detalle{position: absolute;font-size: 12px;font-weight: normal;font: 9pt Verdana, Geneva, sans-serif;}  
            @page{ margin-left: 0px; margin-right: 0px; margin-top: 0px; margin-bottom: 0px; }
        </style>
    </head>
<body>
    <?Php  list($anio, $mes, $dia) = preg_split('![/.-]!', $row_prin_renta['Ret_Fec']);?>
    <!--fecha  --><span style="<?php echo getCss($docp,'fecha'); ?>" class="flota"><?php echo $dia.'/'.$mes.'/'.$anio; ?></span>
    <!--provee--><span style="<?php echo getCss($docp,'proveedor'); ?>" class="flota truncate"><?php echo $row_prin_renta['Prs_Ape'].' '.$row_prin_renta['Prs_Nom']; ?></span>
    <!--direcci--><span style="<?php echo getCss($docp,'direccion'); ?>" class="flota truncate"><?php echo $row_prin_renta['Prs_Dir']; ?></span>
    <!--ci/ruc --><span style="<?php echo getCss($docp,'cedu_ruc'); ?>" class="flota"><?php echo $row_prin_renta['Prs_Ced']; ?></span>
    <!--ciudad --><!--<span style="<?php echo getCss($docp,'ciudad'); ?>" class="flota truncate"><?php //echo $row_prin_renta['Ciu_Des']; ?></span>-->
    
    <!--documento--><span style="<?php echo getCss($docp,'docu'); ?>" class="flota truncate"><?php echo $row_prin_renta['Tic_Des']; ?></span>
    <!--docu_nume--><span style="<?php echo getCss($docp,'docu_num'); ?>" class="flota"><?php echo $row_prin_renta['Cop_Num']; ?></span>
    <!--docu_fech--><!--<span style="<?php echo getCss($docp,'docu_fech'); ?>" class="flota"><?php echo $row_prin_renta['Cop_Fec']; ?></span>-->
    <!--docu_auto--><!--<span style="<?php echo getCss($docp,'docu_auto'); ?>" class="flota"><?php echo $row_prin_renta['Cop_Aut']; ?></span>-->
    <!--docu_cadu--><!--<span style="<?php echo getCss($docp,'docu_cadu'); ?>" class="flota"><?php echo $row_prin_renta['Cop_Cad']; ?></span>-->
    
    <?php 
    $aux=getCss($docp,'item','y');
    $Total_Ret=0; $Eje_Fis=explode('-',$row_prin_renta['Ret_Fec']); $Ejerci=$Eje_Fis[0];
    foreach($rs_prin_renta as $row){ ?>
        <span style="<?php echo "top:{$aux}px;left:".getCss($docp,'item_ejer','x')."px;width:".getCss($docp,'item_cant','width')."px;text-align:center;" ?>" class="flota number"><?php echo $Ejerci;?></span>
        <span style="<?php echo "top:{$aux}px;left:".getCss($docp,'item_codi','x')."px;width:".getCss($docp,'item_codi','width')."px;" ?>" class="flota truncate"><?php echo $row['Ren_Sri'].'-'.$row['Ret_Imp']?></span>
        <span style="<?php echo "top:{$aux}px;left:".getCss($docp,'item_base','x')."px;width:".getCss($docp,'item_base','width')."px;" ?>" class="flota number"><?php echo number_format($row['Ret_Bas'], 2,'.',','); ?></span>
        <span style="<?php echo "top:{$aux}px;left:".getCss($docp,'item_porc','x')."px;width:".getCss($docp,'item_porc','width')."px;text-align:center;" ?>" class="flota number"><?php echo $row['Ren_Por'].'%'; ?></span>
        <span style="<?php echo "top:{$aux}px;left:".getCss($docp,'item_rete','x')."px;width:".getCss($docp,'item_rete','width')."px;" ?>" class="flota number"><?php  echo $Val_Ret=formato_numero((formato_numero($row['Ret_Bas'],2,1)* $row['Ren_Por'])/100,2,1); $Total_Ret+=$Val_Ret; ?></span>
    <?php unset($Ejerci); $aux+=20; } ?>    
    
    <!--Total--><span style="<?php echo getCss($docp,'total'); ?>text-align:right;" class="flota"><strong><?Php echo number_format ($Total_Ret, 2,'.',''); ?></strong></span>

</body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>