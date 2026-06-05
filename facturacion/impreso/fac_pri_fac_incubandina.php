<?php include('../IMPRIMIR/datos_fact.php') ?>
<html>
    <head>
        <title><?Php echo $Ses_Sys_Nom; ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">    
        <style type="text/css">   
             body{width:830px;padding:0;margin:0;}
            .flota{position: absolute;font-size: 12px;font-weight: normal;font: 11pt Arial, Helvetica, sans-serif;}
            .flota.truncate{white-space:nowrap;overflow:hidden; font-stretch:condensed;line-height: 16px; letter-spacing: -0.3px; }
            .flota.number{text-align:right; letter-spacing: -0.3px; }
            .detalle{position: absolute;font-size: 12px;font-weight: normal;font: 9pt Verdana, Geneva, sans-serif;}            
            @page{ margin-left: 0px; margin-right: 0px; margin-top: 0px; margin-bottom: 0px; }
        </style>
    </head>
<body>
    <?Php  list($anio, $mes, $dia) = preg_split('![/.-]!', $row_rs_cliente['Caj_Fec']);?>
    <!--fecha  --><span style="<?php echo getCss($docp,'fecha'); ?>" class="flota"><? echo $dia.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$mes.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.substr($anio,2,4); ?></span>
    <!--cliente--><span style="<?php echo getCss($docp,'cliente'); ?>" class="flota truncate"><? if ($row_rs_representante['Cli_Fac'] != ""){echo $row_rs_representante['Cli_Fac'];}else{ echo $row_rs_cliente['Prs_Ape'].' '.$row_rs_cliente['Prs_Nom'];}?></span>
    <!--direcci--><span style="<?php echo getCss($docp,'direccion'); ?>" class="flota truncate"><? if ($row_rs_representante['Cli_Dir'] != ""){echo substr($row_rs_representante['Cli_Dir'],0,31);}else{echo substr($row_rs_cliente['Prs_Dir'],0,31);}?></span>
    <!--ci/ruc --><span style="<?php echo getCss($docp,'cedu_ruc'); ?>" class="flota"><? if ($row_rs_representante['Cli_Fac'] != ""){echo $row_rs_representante['Cli_Ruf'];}else{echo $row_rs_cliente['Prs_Ced'];}?></span>
    <!--ciudad --><span style="<?php echo getCss($docp,'ciudad'); ?>" class="flota truncate"><? echo $row_institucion['Ciu_Des'];?></span>
    
    <? $aux=getCss($docp,'item','y');
    do{?>
        <span style="<? echo "top:{$aux}px;left:".getCss($docp,'item_cant','x')."px;width:".getCss($docp,'item_cant','width')."px;" ?>" class="flota number"><? echo round($row_rs_cliente['Vet_Can'],1);?></span>
        <span style="<? echo "top:{$aux}px;left:".getCss($docp,'item_desc','x')."px;width:".getCss($docp,'item_desc','width')."px;" ?>" class="flota truncate"><? echo $row_rs_cliente['Ite_Cor'].' '.$row_rs_cliente['Pro_Obs'];?></span>
        <span style="<? echo "top:{$aux}px;left:".getCss($docp,'item_prun','x')."px;width:".getCss($docp,'item_prun','width')."px;font-stretch:condensed;line-height: 16px;" ?>" class="flota number"><? echo number_format($row_rs_cliente['Vet_Pru'], 2);?></span>
        <span style="<? echo "top:{$aux}px;left:".getCss($docp,'item_impo','x')."px;width:".getCss($docp,'item_impo','width')."px;" ?>" class="flota number"><? echo number_format($row_rs_cliente['Vet_Imp'], 2);?></span>

    <? $aux+=26; }while ($row_rs_cliente = $obBD_con1->fetch_assoc ($rs_cliente));

    ?>
    <!--Tipo Pago --><span style="<?php echo getCss($docp,'pago'); ?>" class="flota"><? echo 'x'; ?></span>  
    <!--Subtotal  --><span style="<?php echo getCss($docp,'subtot'); ?>text-align:right;" class="flota"><?Php echo formato_numero($resultados[0], 2, 1); ?></span>
    <!--Descuento --><!--<span style="<?php echo getCss($docp,'descue'); ?>text-align:right;" class="flota"><?Php echo formato_numero($resultados[4], 2, 1); ?></span>-->
    <!--Tarifa 0% --><!--<span style="<?php echo getCss($docp,'subt_0'); ?>text-align:right;" class="flota"><?Php echo formato_numero($resultados[1]+0, 2, 1); ?></span>-->
    <!--Tarifa 12%--><!--<span style="top:<? //echo $posTot+100;?>px;left:270px;" class="flota"><?Php //echo formato_numero($resultados[2]+0, 2, 1); ?><!--</span>-->
    <!--IVA       --><span style="<?php echo getCss($docp,'iva'); ?>text-align:right;" class="flota"><?Php echo formato_numero($resultados[3], 2, 1); ?></span>
    
    <!--Total--><span style="<?php echo getCss($docp,'total'); ?>text-align:right;" class="flota"><!--TOTAL&nbsp;--><strong><?php echo number_format($resultados[5], 2); ?></strong></span>

</body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>