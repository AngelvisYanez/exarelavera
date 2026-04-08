<?php

/* 
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */
?>
<style>
    .footrow td[aria-describedby="documento_Cop_Imp"],
    .footrow td[aria-describedby="documento_Cop_Pru"] {
        padding: 0 !important;
    }

    .footerFact {
        text-align: right;
        width: 100%;
    }

    .footerFact input[type=text],
    .footerFact label,
    .footerFact textarea,
    .footerFact select {
        height: 19px;
        width: 100% !important;
        display: block;
        margin-bottom: 0px !important;
        margin-top: 0px !important;
        text-align: right;
    }

    .footerFact input[type=text] {
        padding: 0;
    }

    .footerFact textarea {
        text-align: left;
        height: 75px !important;
    }

    .footerFact select {
        padding-top: 2px !important;
        padding-bottom: 2px !important;
        display: inline;
    }

    .footerFact label {
        height: 19px;
        line-height: 18px;
        padding-right: 5px;
    }

    .footerFact label.total,
    .footerFact input.total {
        background-color: #254463;
        color: white;
        font-size: 14px;
        border: none;
    }

    #Ret_Asu {
        vertical-align: middle;
        margin-top: -2px;
        padding: 5px;
        -ms-transform: scale(1.4);
        -moz-transform: scale(1.4);
        -webkit-transform: scale(1.4);
        -o-transform: scale(1.4);
    }

    #resultContent .resp {
        font-weight: 700;
        font-size: 30px;
        color: #3f3fc1;
        padding: 0;
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        height: 32px;
    }

    #resultContent .resp span:first-child {
        color: darkgoldenrod;
        width: 100px;
        display: inline-block;
        margin-left: 42px;
    }

    .ret .input-group-btn button {
        padding: 1px 2px !important;
    }

    .ret {
        padding: 0 !important;
    }
</style>



<form id="formDocumento" class="form-horizontal normal formDatos" action="javascript:validaDocument();">
    <!--ivas-->
    <select id="Def_Ivas" name="Def_Ivas" class="form-control input-xs" style="display: none;">
        <?php
        $temp = array();
        foreach ($ivas as $row) {
            if (!in_array($row['Iva_Por'], $temp)) {
                echo '<option value="' . $row['Iva_Cod'] . '" data-ivapor="' . $row['Iva_Por'] . '" data-ivaini=' . $row['Iva_Ini'] . ' data-ivafin=' . $row['Iva_Fin'] . ' >' . $row['Iva_Por'] . ' %</option>';
            }
            array_push($temp, $row['Iva_Por']);
        }
        ?>
    </select>

    <!--tipos_pago-->
    <select id="pag_cod" name="pag_cod" class="form-control input-xs" style="display: none;">
        <?php if (isset($tipospago)) foreach ($tipospago as $row) { ?>
            <option value="<?php echo $row['Pag_Cod']; ?>" data-forcod="<?php echo $row['For_Cod']; ?>"><?php echo utf8_decode($row['Pag_Des']); ?></option>
        <?php } ?>
    </select>

    <!--bancos-->
    <select id="bak_cod" name="bak_cod" class="form-control input-xs" style="display: none;">
        <?php if (isset($bankos)) foreach ($bankos as $row) { ?>
            <option value="<?php echo $row['Bak_Cod']; ?>"><?php echo utf8_decode($row['Bak_Des']); ?></option>
        <?php } ?>
    </select>

    <!--cuentas contado=1, credito=2-->
    <select id="pld_cod" name="pld_cod" class="form-control input-xs" style="display: none;"></select>

    <div class="row">
        <div class="col-xs-5">
            <fieldset class="exa-fieldset" id="clieFormTemp">
                <legend class="Titulos2">Datos del Cliente</legend>

                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Cédula/RUC:</label>
                    <div class="col-xs-6">
                        <input name="Prs_Cod" type="text" style="display:none;" />
                        <!-- <input name="Prv_Cod" type="text" style="display:none;" /> -->
                        <input name="Cli_Cod" type="text" style="display:none;" />
                        <input name="op_opciones" type="text" value="c" style="display: none;">
                        <div class="input-group input-group-xs">
                            <input name="Prs_Ced" onkeydown="if (event.keyCode === 13) $.SearchOrDialog('#clieDialog',selectCliente);" type="text" placeholder="Ingrese Cliente..." class="form-control input-xs clearable dialogSearch" tabindex="1" />
                            <span class="input-group-btn">
                                <button id="Cli_Btn" type="button" onclick="$('#clieDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Cliente" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                <!-- <button id="Rgt_Btn" type="button" onclick="$('#clieCreateForm').setData({}).find('.validate').find('i').removeAttr('class'); $('#clieCreateDialog').dialog('open');" class="btn btn-success btn-xs" title="Registrar Cliente" tabindex="2"><span class="glyphicon glyphicon-plus"></span></button> -->
                                <button id="Rgt_Btn" type="button" onclick="$('#clieCreateForm').setData({Prv_Esp:'N',Prv_Con:'N'}).find('.validate').find('i').removeAttr('class'); $('#clieCreateDialog').dialog('open');" class="btn btn-success btn-xs" title="Registrar Cliente" tabindex="2"><span class="glyphicon glyphicon-plus"></span></button>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Cliente:</label>
                    <div class="col-xs-10"><span name="cliente" class="form-control input-xs databind datatitle"></span></div>
                </div>

                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Dirección:</label>
                    <div class="col-xs-10">
                        <div class="input-group input-group-xs">
                            <input name="Prs_Dir" type="text" class="form-control span datatitle" readonly="" tabindex="-1">
                            <span class="input-group-addon bold">Correo:</span>
                            <input name="Prs_Cor" type="text" class="form-control span datatitle" readonly="" tabindex="-1" />
                        </div>
                    </div>
                </div>

            </fieldset><!-- FIN DEL BLOQUE DE DATOS DEL CLIENTE -->

            <?php $bodegas = $obBD_con1->getArrayConsulta('bodega.1', array('Suc_Cod' => $Ses_Suc_Cod, 'Usu_Cod' => $Ses_Usu_Cod), $obBD_conexion); ?>

            <fieldset class="exa-fieldset" <?php if (count($cen_cons) == 0 and count($bodegas) == 0) echo 'style="display:none; "'; ?>>
                <legend class="Titulos2"></legend>
                <div class="form-group col-xs-6" <?php if (count($bodegas) == 0) echo 'style="display:none; "'; ?>>
                    <label class="col-xs-3 control-label label-xs">Bodega:</label>
                    <div class="col-xs-9">
                        <select id="Bod_Cod" name="Bod_Cod" class="form-control input-xs">
                            <?php if (count($bodegas) > 0) foreach ($bodegas as $row) {
                                echo "<option value='$row[Bod_Cod]'>$row[Bod_Nom]</option>";
                            } ?>
                        </select>
                    </div>
                </div>
            </fieldset>
        </div>

        <div class="col-xs-7"><!-- BLOQUE DE DATOS DEL DOCUMENTO -->
            <fieldset class="exa-fieldset" id="docuFormTemp">
                <legend class="Titulos2">Datos del Documento</legend>
                <input type="text" name="Vet_Cod" style="display: none;" />
                <input type="text" name="Com_Cod" style="display: none;" />

                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Periodo:</label>
                    <div class="col-xs-2">
                        <!-- <select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs">
                            <?php $rs_perio = $obBD_con1->getArrayConsulta(5, $Ses_Emp_Cod, $obBD_conexion);
                            foreach ($rs_periodo as $row) { ?>
                                <option value="<?php echo $row['Pec_Cod']; ?>" data-inicio="<?php echo $row['Pec_Fei']; ?>" data-fin="<?php echo $row['Pec_Fef']; ?>"><?php echo $row['Anio']; ?></option>
                            <?php   } ?>
                        </select> -->
                        <input type="text" id="Pec_Cod" name="Pec_Cod" class="form-control input-xs">
                    </div>
                    <label class="col-xs-1 control-label label-xs">Fecha:</label>
                    <div class="col-xs-3">
                        <input type="text" id="Caj_Fec" name="Caj_Fec" class="form-control input-xs datepickers">
                    </div>
                    <label class="col-xs-1 control-label label-xs">Ciudad:</label>
                    <div class="col-xs-3">
                        <?php $rs_ciudad = $obBD_con1->getArrayConsulta('ciudad.selectWhere', array('clean' => true, 'join' => array('provincia' => array('on' => 'provincia.Pro_Cod=ciudad.Pro_Cod', 'cols' => 'Pro_Nom')), 'where' => "Ciu_Des != ''", 'order' => 'Ciu_Des'), $obBD_conexion); ?>
                        <select name="Ciu_Cod" id="Ciu_Cod" class="form-control input-xs" data-placeholder="Seleccione..." tabIndex="7">
                            <option value=""></option>
                            <?php foreach ($rs_ciudad as $row) {
                                //echo "<option value='$row[Ciu_Cod]' data-prov='$row[Pro_Nom]'>$row[Ciu_Des]</option>";
                                echo "<option value='{$row['Ciu_Cod']}' data-prov='" . utf8_encode($row['Pro_Nom']) . "'>" . utf8_encode($row['Ciu_Des']) . "</option>";
                            } ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs required">Docum.:</label>
                    <div class="col-xs-6">
                        <select id="Tic_Cod_aux" name="Tic_Cod_aux" class="form-control input-xs" required="">
                            <?php foreach ($rs_tip_compr as $row) {
                                //if ($row['Tic_Sri'] != 4 && $row['Tic_Sri'] != 5 && $row['Tic_Sri'] != 7 && $row['Tic_Sri'] != 23 && $row['Tic_Sri'] != 24)
                                echo "<option value='$row[Tic_Cod]' data-ticsri='$row[Tic_Sri]'>$row[Tic_Sri] - $row[Tic_Des]</option>";
                            } ?>
                        </select>
                    </div>

                    <label class="col-xs-1 control-label label-xs">Aut.:</label>
                    <div class="col-xs-3">
                        <div class="col-xs-12 input-group input-group-xs">
                            <span id="Aut_Sri" name="Aut_Sri" class="form-control input-xs databind"></span>
                            <span id="cambiarAut" class="btn btn-block btn-success input-group-addon " title="Cambiar de Autorizacion">
                                <i class="glyphicon glyphicon-transfer white"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs required">Numero:</label>
                    <div class="col-xs-5">
                        <div class="input-group input-group-xs">
                            <span id="Pun_Sri" name="Pun_Sri" class="input-group-addon alert-info"></span>
                            <input type="text" id="Vet_Num" name="Vet_Num" class="form-control input-xs trigger" tabindex="5" required="" data-container="body" data-toggle="popover" />
                            <span class="input-group-addon validate"><i></i></span>
                        </div>
                    </div>

                    <div class="form-check hidden" id="div_check_comp">
                        <div class="col-xs-5">
                            <label class="form-check-label">
                                <input type="checkbox" id="Check_Comprobante" value=1 name="Check_Comprobante" class="form-check-input">
                                Crear Comprobante
                            </label>
                        </div>
                    </div>
                </div>
            </fieldset>
        </div> <!-- FIN DEL BLOQUE DE DATOS DEL DOCUMENTO -->
    </div>
</form> <!-- FIN DEL FORM -->

<!-- BLOQUE DE CARGA DE DATOS PARA EL GRIDVIEW -->
<div class="row">
    <div class="col-xs-12" style="min-height: 200px; padding-bottom: 5px;">
        <table id="itemsGrid"></table> <!-- GRIDVIEW DE LOS PRODUCTOS -->
        <div id="itemsGridPager"></div> <!-- PAGINADOR DEL GRIDVIEW -->
    </div>
</div>

<div class="row form-horizontal normal"> <!-- INICIO DEL BLOQUE DE DATOS DE LA RETENCION -->
    <div class="col-xs-6">
        <fieldset class="exa-fieldset">
            <legend class="Titulos2">Datos de la Retención</legend>

            <form id="reteFormTemp" action="javascript:" class="formDatos">
                <input type="text" name="Ret_Cod" style="display: none;" id="Ret_Cod" />
                <input type="text" name="Ret_Xml" style="display: none;" />
                <input type="text" name="Aut_Cod" style="display: none;" id="Aut_Cod_Old" />

                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs required">Número:</label>
                    <div class="col-xs-4">
                        <input type="text" name="Aut_Tem" style="display: none;" />
                        <div class="input-group input-group-xs">
                            <input id="Ret_Num" name="Ret_Num" type="text" class="form-control input-xs readOnly ret_field numeric" required="" />
                            <span class="input-group-addon validate"><i></i></span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs required">Autoriza:</label>
                    <div class="col-xs-4">
                        <input name="Ret_Aut_Sri" class="form-control input-xs ret_field" />
                    </div>
                    <label class="col-xs-2 control-label label-xs required">Fecha:</label>
                    <div class="col-xs-4">
                        <div class="input-group">
                            <?php    ?>
                            <input id="Ret_Fec_aux" name="Ret_Fec_aux" type="text" class="form-control input-xs ret_field datepickers" required="" />
                            <span class="input-group-addon input-xs" title="Fecha de la Retención"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                        </div>
                    </div>
                </div>

                <div class="form-group reteTot cod_banano" style="display:none;">
                    <label class="col-xs-2 control-label label-xs required">Banano:</label>
                    <div class="col-xs-8">
                        <div class="input-group input-group-xs">
                            <span class="input-group-addon bold alert-warning">&nbsp;Cod. 338&nbsp;&nbsp;<i class="glyphicon glyphicon-arrow-right"></i>&nbsp;</span>
                            <span class="input-group-addon bold alert-success" title="Cajas de Banano">Cajas:</span>
                            <input name="Ret_Uca" type="text" class="form-control span" style="text-align: right;" pattern="\d*" placeholder="0" />
                            <span class="input-group-addon bold alert-success" title="Precio Unitario por Caja">P.Unit.:</span>
                            <input name="Ret_Pca" type="text" class="form-control span" style="text-align: right;" pattern="\d*" placeholder="0.00" />
                        </div>
                    </div>
                </div>

                <div class="form-group reteTot">
                    <label class="col-xs-2 control-label label-xs"></label>
                    <div class="col-xs-10">
                        <div class="input-group input-group-xs">
                            <span class="input-group-addon bold alert-info">Renta:</span>
                            <input id="Ret_Ren_Tot" name="Ret_Ren_Tot" type="text" class="form-control span" style="text-align: right;" readonly="" />
                            <span class="input-group-addon bold alert-info">+&nbsp;IVA:</span>
                            <input ide="Iva_Ren_Tot" name="Iva_Ren_Tot" type="text" class="form-control span" style="text-align: right;" readonly="" />
                            <span class="input-group-addon bold alert-info">=&nbsp;Retenido:</span>
                            <input id="Ren_Tot" name="Ren_Tot" type="text" class="form-control span" style="text-align: right;" readonly="" />
                            <span class="input-group-btn">
                                <button type="button" onclick="$('#retDetaDialog').dialog('open')" class="btn btn-info" title="Ver Detalle Retención" tabindex="-1"><span class="glyphicon glyphicon-eye-open"></span></button>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="form-group reteTot">
                    <label class="col-xs-5 control-label label-xs"></label>
                    <div class="col-xs-7">
                        <div class="input-group input-group-sm">
                            <span class="input-group-addon bold alert-warning">Monto A Pagar&nbsp;&nbsp;<i class="glyphicon glyphicon-arrow-right"></i></span>
                            <span class="input-group-addon bold alert-success"><i class="glyphicon glyphicon-usd"></i></span>
                            <input id="Val_Pcc" name="Val_Pcc" type="text" class="form-control bold span" style="text-align: right;font-size: 15px; background-color: white;" readonly="" />
                            <span id="infoLiquida" class="input-group-addon validate" style="display:none;"><i></i></span>
                        </div>
                    </div>
                </div>
            </form>
        </fieldset>

        <div class="row"> <!-- BLOQUE DE CARGA DE LOS PAGOS DEL GRIDVIEW -->
            <div class="col-xs-12" style="min-height: 100px; padding-bottom: 5px;">
                <table id="pagosGrid"></table> <!-- GRIDVIEW DE LOS PAGOS -->
                <div id="pagosGridPager"></div> <!-- PAGINADOR DEL GRIDVIEW -->
            </div>
        </div>
    </div> <!-- FIN DEL BLOQUE DE DATOS DE LA RETENCION -->

    <div class="col-xs-6 gridProductosCalculo"> <!-- INICIO DEL BLOQUE DE FORMAS DE LOS PAGOS -->
        <div id="divReembolsos">
            <fieldset class="exa-fieldset">
                <legend class="Titulos2"><input type="checkbox" id="Vet_Rem" name="Vet_Rem" class="check-big" onchange="setReembolsosGrid($(this));" />&nbsp;&nbsp;Reembolsos</legend>
                <div class="condensed" id="gridReembolsos">
                    <table id="reembolsos"></table>
                    <div id="reembolsosPager"></div>
                </div>
            </fieldset>
        </div>

        <form id="pagoFormTemp" action="javascript:" class="formDatos">
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Forma de Pago</legend>
                <input type="text" name="Cpc_Cod" style="display: none;" />
                <div class="form-group pagoSri">
                    <label class="col-xs-3 control-label label-xs required">Pago&nbsp;SRI:</label>
                    <div class="col-xs-9">
                        <?php $rs_pag_sri = $obBD_con1->getArrayConsulta('tipopagocom.selectWhere', array('clean' => true, 'where' => array('Tpc_Est' => 'A')), $obBD_conexion); ?>
                        <select id="Tpc_Cod" name="Tpc_Cod" class="form-control input-xs readOnly" required="" onchange="">
                            <option value="">Seleccione...</option>
                            <?php foreach ($rs_pag_sri as $row) {
                                $selected = '';
                                if ($row['Tpc_Sri'] == 1) {
                                    $selected = 'Selected';
                                }
                                //echo "<option value='$row[Tpc_Cod]' >$row[Tpc_Sri] - $row[Tpc_Des]</option>";
                                echo "<option value='" . $row['Tpc_Cod'] . "' " . $selected .  " >" . utf8_encode($row['Tpc_Sri']) . " - " . utf8_encode($row['Tpc_Des']) . "</option>";
                            } ?>
                        </select>
                    </div>
                </div>

                <div class="form-group porCobrar">
                    <label class="col-xs-3 control-label label-xs"></label>
                    <div class="col-xs-9">
                        <div class="input-group input-group-sm">
                            <span class="input-group-addon bold alert-warning">Por Cobrar&nbsp;&nbsp;<i class="glyphicon glyphicon-arrow-right"></i></span>
                            <span class="input-group-addon bold alert-success"><i class="glyphicon glyphicon-usd"></i></span>
                            <input id="Val_Pcc_2" name="Val_Pcc_2" type="text" class="form-control bold span" style="text-align: right;font-size: 15px; background-color: white;" readonly="" tabindex="-1">

                        </div>
                    </div>
                </div>
            </fieldset>
        </form>
    </div> <!-- FIN DEL BLOQUE DE FORMAS DE LOS PAGOS -->
</div>

<!-- DIALOGO DETALLE RETENCION -->
<div id="retDetaDialog" title="Retención">
    <div class="condensed-header">
        <table id="retencion"></table>
    </div>
</div>