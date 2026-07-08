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
    <div class="row">
        <div class="col-xs-5">
            <fieldset class="exa-fieldset" id="provFormTemp">
                <legend class="Titulos2">Datos del Proveedor</legend>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Cédula/RUC:</label>
                    <div class="col-xs-6">
                        <input name="Prs_Cod" type="text" style="display:none;" />
                        <input name="Prv_Cod" type="text" style="display:none;" />
                        <input name="op_opciones" type="text" value="c" style="display: none;">
                        <div class="input-group input-group-xs">
                            <input name="Prs_Ced" onkeydown="if (event.keyCode === 13) $.SearchOrDialog('#provDialog',selectProvee);" type="text" placeholder="Ingrese Proveedor..." class="form-control input-xs clearable dialogSearch" tabindex="1" />
                            <span class="input-group-btn">
                                <button id="Prv_Btn" type="button" onclick="$('#provDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Proveedor" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                <button type="button" onclick="$('#provCreateForm').setData({Prv_Esp:'N',Prv_Con:'N'}).find('.validate').find('i').removeAttr('class'); $('#provCreateDialog').dialog('open');" class="btn btn-success btn-xs" title="Registrar Proveedor" tabindex="2"><span class="glyphicon glyphicon-plus"></span></button>
                            </span>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <span class="radioset">
                            <input id="op_ide1" name="Cop_Ide" type="radio" value="1" disabled style="cursor:pointer" onchange=""><label title="Documento del proveedor tipo R.U.C" for="op_ide1">&nbsp;Ruc&nbsp; </label>
                            <input id="op_ide2" name="Cop_Ide" type="radio" value="2" disabled style="cursor:pointer" onchange=""><label title="Documento del proveedor tipo CEDULA" for="op_ide2">&nbsp;Ced&nbsp;</label>
                            <input id="op_ide3" name="Cop_Ide" type="radio" value="3" disabled style="cursor:pointer" onchange=""><label title="Documento del proveedor tipo PASAPORTE" for="op_ide3">&nbsp;Pas&nbsp;</label>
                        </span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs required">Proveedor:</label>
                    <?php if (!isset($insert)) { ?>
                        <div class="col-xs-6"><span name="proveedor" class="form-control input-xs databind datatitle"></span></div>
                    <?php } else { ?>
                        <div class="col-xs-6">
                            <div class="input-group input-group-xs">
                                <span name="proveedor" class="form-control input-xs databind datatitle"></span>
                                <span class="input-group-btn"> <button type="button" id='cargarElectronico' onclick="$('#formElectronico').setData({}); $('#loadXml').dialog('open');   " class="btn btn-success btn-xs" title="Cargar Documento Electrónico" tabindex="-1"><span class="fa fa-globe"></span></button> </span>
                            </div>
                        </div>
                    <?php } ?>
                    <label class="col-xs-4 control-label label-xs">Oblig.Contab:&nbsp;<i id="Prv_Con" class="blue glyphicon glyphicon-remove" style="font-size: 12px;"></i></label>
                    <label class="col-xs-4 control-label label-xs">Contr.Especial:&nbsp;<i id="Prv_Esp" class="blue glyphicon glyphicon-remove" style="font-size: 12px;"></i></label>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Dirección:</label>
                    <div class="col-xs-10">
                        <div class="input-group input-group-xs">
                            <input name="Prs_Dir" type="text" class="form-control span datatitle" readonly="" tabindex="-1">
                            <span class="input-group-addon bold">e-mail:</span>
                            <input name="Prs_Cor" type="text" class="form-control span datatitle" readonly="" tabindex="-1" />
                        </div>
                    </div>
                <?php if ($configs['Cof_Sld'] == 'S') { ?>
                        <label class="col-xs-2 control-label label-xs" style="text-decoration: underline; margin-left: -10px;">Saldo de CCxPP:</label>
                        <div class="col-xs-4" style="margin-top: 10px;margin-left: 10px;">
                            <input id="Prv_Sal" name="Prv_Sal" type="text" class="form-control input-xs databind" style="text-align: right;" readonly />
                        </div>
                    </div>
                <?php } ?>
            </fieldset>


            <!-- Negociacion -->
            <?php if ($rs_infoEmpresa["Cof_NegCam"] == 'S') { ?>
                <fieldset class="exa-fieldset" id="formNeg">
                    <legend class="Titulos2">Negociación:</legend>
                    <div class="form-group">
                        <label class="col-xs-3 control-label label-xs">Negociación:</label>
                        <div class="col-xs-9">
                            <div class="input-group input-group-xs">
                                <input type="text" name="Num_Neg" id="Num_Neg" placeholder="Ingrese cod.Negociación..." class="form-control input-xs " readonly />
                                <input type="text" name="Cod_Neg" id="Cod_Neg" style="display:none;" />
                                <input type="text" name="Cod_Nd" id="Cod_Nd" style="display:none;" />
                                <span class="input-group-btn">
                                    <button id="Prv_Btn_" type="button" onclick="$('#negDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Negociación" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                    <button type="button" onclick="limpiarCamposNego()" class="btn btn-success btn-xs"><span class="glyphicon glyphicon-remove"></span></button>
                                </span>
                            </div>
                        </div>
                    </div>
                </fieldset>
            <?php } ?>


            <?php $cen_cons = $obBD_con1->getArrayConsulta('consumo.selectWhere', array('clean' => true, 'where' => array('Emp_Cod' => $Ses_Emp_Cod, 'Con_Est' => 'A')), $obBD_conexion); ?>
            <?php $bodegas = $obBD_con1->getArrayConsulta('bodega.1', array('Suc_Cod' => $Ses_Suc_Cod, 'Usu_Cod' => $Ses_Usu_Cod), $obBD_conexion); ?>

            <fieldset class="exa-fieldset" <?php if (count($cen_cons) == 0 and count($bodegas) == 0) echo 'style="display:none; "'; ?>>
                <legend class="Titulos2"></legend>
                <div class="form-group col-xs-6" <?php if (count($cen_cons) == 0) echo 'style="display:none; "'; ?>>
                    <label class="col-xs-3 control-label label-xs">Consumo:</label>
                    <div class="col-xs-9">
                        <select name="Con_Cod" class="form-control input-xs">
                            <option value="" selected="">NINGUNO</option>
                            <?php if (count($cen_cons) > 0) foreach ($cen_cons as $row) {
                                echo "<option value='$row[Con_Cod]'>$row[Con_Des]</option>";
                            } ?>
                        </select>
                    </div>
                </div>
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
        <div class="col-xs-7">
            <fieldset class="exa-fieldset" id="docuFormTemp">
                <legend class="Titulos2">Datos del Documento</legend>
                <input type="text" name="Cop_Cod" style="display: none;" />
                <input type="text" name="Com_Cod" style="display: none;" />
                <div class="row">
                    <div class="col-xs-12">
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs required">Sustento:</label>
                            <div class="col-xs-10">
                                <?php $rs_sustento = $obBD_con1->getArrayConsulta('sustento.selectWhere', array('clean' => true, 'where' => array('Tri_Est' => 'A')), $obBD_conexion); ?>
                                <select id="Tri_Cod" name="Tri_Cod" class="form-control input-xs" tabindex="3" onchange="tipoComprobanteHide($('option:selected', this).attr('data-ticsri'))" required="">
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($rs_sustento as $row) {
                                        echo "<option value='{$row['Tri_Cod']}' " . ($row['Tri_Cod'] == 2 ? 'selected' : '') . " data-ticsri='" . $row['Tri_Sri'] . "' >" . mb_convert_encoding($row['Tri_Sri'], 'UTF-8', 'ISO-8859-1') . "-" . mb_convert_encoding($row['Tri_Des'], 'UTF-8', 'ISO-8859-1') . "     </option>";
                                    } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs required">Documento:</label>
                            <div class="col-xs-5">
                                <div class="input-group">
                                    <select id="Tic_Cod" name="Tic_Cod" class="form-control input-xs" tabindex="4" onchange="validaCopNum();cambioTipoDoc(this.value)" required="" data-trigger="">
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($rs_tip_compr as $row) {
                                            if ($row['Tic_Sri'] != 4 && $row['Tic_Sri'] != 5 && $row['Tic_Sri'] != 7 && $row['Tic_Sri'] != 23 && $row['Tic_Sri'] != 24)
                                                //echo "<option value='$row[Tic_Cod]' data-ticsri='$row[Tic_Sri]'>$row[Tic_Sri] - $row[Tic_Des]</option>";
                                                echo "<option value='{$row['Tic_Cod']}' data-ticsri='" . mb_convert_encoding($row['Tic_Sri'], 'UTF-8', 'ISO-8859-1') . "'>" . mb_convert_encoding($row['Tic_Sri'], 'UTF-8', 'ISO-8859-1') . " - " . mb_convert_encoding($row['Tic_Des'], 'UTF-8', 'ISO-8859-1') . "</option>";
                                        } ?>
                                    </select>
                                    <span class="input-group-addon input-xs" title="Ingresar autorización" onclick="validaIngresoAut()"><i class="glyphicon glyphicon-erase"></i></span>
                                </div>

                            </div>
                            <label class="col-xs-2 control-label label-xs required">Emision:</label>
                            <div class="col-xs-3">
                                <div class="input-group">
                                    <input id="Cop_Fec" name="Cop_Fec" type="text" class="form-control input-xs datepickers" tabindex="8" required="" />
                                    <span class="input-group-addon input-xs" title="Fecha de Emisión del Proveedor"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                </div>
                            </div>
                            <input type="hidden" id="idCargaExitosa" name="idCargaExitosa">
                        </div>
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs required">Número:</label>
                            <div class="col-xs-5">
                                <div class="input-group input-group-xs">

                                    <span id="Pun_Sri" name="Pun_Sri" class="input-group-addon alert-info"></span>
                                    <input type="text" name="Pun_Sri" id="Pun_Sri" style="display:none;">

                                    <input type="text" id="Cop_Num" name="Cop_Num" onchange="validaCopNum()" class="form-control input-xs secuencia" tabindex="5" required="" />
                                    <span class="input-group-addon validate"><i></i></span>
                                </div>
                            </div>
                            <input type="text" name="Aut_Codliq" style="display: none;" id="Aut_Codliq" />
                            <label class="col-xs-2 control-label label-xs required">Impresión:</label>
                            <div class="col-xs-3">
                                <div class="input-group">
                                    <input id="Cop_Imf" name="Cop_Imf" type="text" class="form-control input-xs datepickers empty" tabindex="9" required="" />
                                    <span class="input-group-addon input-xs" title="Fecha de Creación en Imprenta"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs required">Autoriza:</label>
                            <div class="col-xs-5">
                                <div class="input-group input-group-xs">
                                    <input id="Cop_Aut" type="text" name="Cop_Aut" class="form-control datatitle datatrigger" tabindex="6" required="" maxlength="49" pattern="\d{10}|\d{37}|\d{49}" />
                                    <span class="input-group-addon validate"><i></i></span>
                                </div>
                            </div>

                            <label class="col-xs-2 control-label label-xs required">Caducidad:</label>
                            <div class="col-xs-3">
                                <div class="input-group">
                                    <input id="Cop_Cad" name="Cop_Cad" type="text" class="form-control input-xs datepickers empty" tabindex="10" required="" />
                                    <span class="input-group-addon input-xs" title="Fecha de Caducidad en el SRI"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs required">Ciudad:</label>
                            <div class="col-xs-5">
                                <?php $rs_ciudad = $obBD_con1->getArrayConsulta('ciudad.selectWhere', array('clean' => true, 'join' => array('provincia' => array('on' => 'provincia.Pro_Cod=ciudad.Pro_Cod', 'cols' => 'Pro_Nom')), 'where' => "Ciu_Des != ''", 'order' => 'Ciu_Des'), $obBD_conexion); ?>
                                <select name="Ciu_Cod" id="Ciu_Cod" class="form-control input-xs" data-placeholder="Seleccione..." tabIndex="7">
                                    <option value=""></option>
                                    <?php foreach ($rs_ciudad as $row) {
                                        //echo "<option value='$row[Ciu_Cod]' data-prov='$row[Pro_Nom]'>$row[Ciu_Des]</option>";
                                        echo "<option value='{$row['Ciu_Cod']}' data-prov='" . mb_convert_encoding($row['Pro_Nom'], 'UTF-8', 'ISO-8859-1') . "'>" . mb_convert_encoding($row['Ciu_Des'], 'UTF-8', 'ISO-8859-1') . "</option>";
                                    } ?>
                                </select>
                            </div>
                            <?php if ($configs['Cof_Con'] == 'S') { ?>
                                <label class="col-xs-2 control-label label-xs required">Comprobante:</label>
                                <div class="col-xs-3">
                                    <div class="input-group">
                                        <input id="Com_Fec" name="Com_Fec" type="text" class="form-control input-xs datepickers" tabindex="11" required="" />
                                        <span class="input-group-addon input-xs" title="Fecha del Comprobante de Egreso/Diario"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </fieldset>
        </div>
    </div>
</form>
<div class="row gridProductosCalculo">
    <div class="col-xs-12" style="min-height: 200px; padding-bottom: 5px;">
        <table id="documento"></table>
        <div id="documentoPager"></div>
    </div>
</div>
<div class="row form-horizontal normal">
    <div class="col-xs-6">
        <fieldset class="exa-fieldset">
            <legend class="Titulos2">Datos de la Retención</legend>



            <form id="reteFormTemp" action="javascript:" class="formDatos">
                <input type="text" name="Ret_Cod" style="display: none;" id="Ret_Cod" />
                <input type="text" name="Ret_Xml" style="display: none;" />
                <input type="text" name="Aut_Cod" style="display: none;" id="Aut_Cod_Old" />

                <div class="form-group">
                    <?php if ($rs_infoEmpresa['Ret_Scom'] == "S") { ?>
                        <div class="col-xs-12 control-label label-xs text-success" style="padding:10px;">
                            <small>Tiene activada la opción de generar retención sin comprobante</small>
                        </div>
                    <?php }  ?>

                    <label class="col-xs-2 control-label label-xs required">Número:</label>
                    <div class="col-xs-4">
                        <input type="text" name="Aut_Tem" style="display: none;" />
                        <div class="input-group input-group-xs">
                            <input id="Ret_Num" name="Ret_Num" type="text" class="form-control input-xs readOnly ret_field numeric" onchange="validaRetNum()" required="" />
                            <span class="input-group-addon validate"><i></i></span>
                            <span class="input-group-btn">
                                <button id="btnClaveExterna" type="button" onclick="changeElect()" class="btn btn-success btn-xs" title="Retencion Electronica Externa" tabindex="-1" style="display: none;"><span class="fa fa-globe"></span></button>
                            </span>
                        </div>
                    </div>
                    <script>
                        function changeElect() {
                            $('.claveExterna').toggleCss('display', 'none');
                            $('#claveAccesoExt').toggleAttr('required');
                            $('#claveAccesoExt').val("");
                            $("#isClaveExterna").val($('.claveExterna').is(":visible") ? "1" : "");
                        }
                    </script>
                    <div class="col-xs-3">
                        <?php if ($configs['Cof_Con'] == 'S') { ?>
                            <?php $row_rs_RetPld = $obBD_con1->getArrayConsulta('plan_param.selectWhere', array('where' => array('Tpa_Abr' => 'RA', 'Pld_Est' => 'A'), 'setWhere' => 'setEmpCod'), $obBD_conexion); ?>
                            <?php //$row_rs_RetPld = $obBD_con1->getArrayConsulta(67, $Ses_Emp_Cod.'*'.'RA',$obBD_conexion); 
                            ?>
                            <div id="asumirRet" style="display:none;">
                                <input type="text" name="Ret_Pld_Cod" value="<?php if (count($row_rs_RetPld) > 0) echo $row_rs_RetPld[0]['Pld_Cod']; ?>" style="display: none" />
                                <input type="checkbox" id="Ret_Asu" name="Ret_Asu" value="S" offval="N" <?php if (count($row_rs_RetPld) === 0) echo 'disabled="disabled" title="No se ha parametrizado una cuenta contable."'; ?>><label class="control-label label-xs">&nbsp;&nbsp;Asumir Retención <i class="glyphicon glyphicon-info-sign blue" title="<?php if (count($row_rs_RetPld) === 0) echo 'No se ha parametrizado una cuenta contable.';
                                                                                                                                                                                                                                                                                                                                                            else echo 'Asumir el Valor de la Retención Contablemente'; ?>"></i></label>
                            </div>
                        <?php } ?>
                    </div>
                    <label class="col-xs-3 control-label label-xs  text-left"><span><input type="checkbox" id="edit_fec_ret" name="edit_fec_ret" title="Editar la fecha de la retención.">&nbsp;&nbsp;Editar fecha retención</span> &nbsp; Cód.Int.:&nbsp;<span id="Aut_Cod" class="blue"></span></label>
                    <!--label class="col-xs-2 control-label label-xs">Cód.Int.:&nbsp;<span id="Aut_Cod" class="blue"></span></label-->
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs required">Autoriza:</label>
                    <?php if (!isset($insert)) { ?>
                        <div class="col-xs-4">
                            <div class="input-group input-group-xs"><span name="Aut_Sri" class="form-control input-xs databind"></span>
                                <span class="input-group-btn"><button type="button" onclick="$('#autorizaDialog').dialog('open');" class="btn btn-success btn-xs" title="Cambiar Autorización" tabindex="-1"><span class="glyphicon glyphicon-transfer"></span></button>
                                </span>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="col-xs-4"><span name="Aut_Sri" class="form-control input-xs"></span></div>
                    <?php } ?>

                    <label class="col-xs-2 control-label label-xs required">Fecha:</label>
                    <div class="col-xs-4">
                        <div class="input-group">
                            <input id="Ret_Fec" name="Ret_Fec" type="text" class="form-control input-xs readOnly ret_field datepickers" required="" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" onchange="validaRetFec()" />
                            <span class="input-group-addon input-xs" title="Fecha de la Retención"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                        </div>
                    </div>
                </div>
                <div class="form-group claveExterna" style="display: none;">
                    <div class="col-xs-2"><input type="hidden" id="isClaveExterna" name="isClaveExterna" value=""></div>
                    <div class="col-xs-10">
                        <div class="input-group input-group-xs">
                            <span class="input-group-addon bold alert-info">Clave Acceso:</span>
                            <input type="text" id="claveAccesoExt" name="claveAccesoExt" onkeypress="return validar_numeric(event);" onchange="$(this).prop('title', this.value);" minlength="49" maxlength="49" class="form-control" />
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
                            <input name="Ret_Ren_Tot" type="text" class="form-control span" style="text-align: right;" readonly="" />
                            <span class="input-group-addon bold alert-info">+&nbsp;IVA:</span>
                            <input name="Iva_Ren_Tot" type="text" class="form-control span" style="text-align: right;" readonly="" />
                            <span class="input-group-addon bold alert-info">=&nbsp;Retenido:</span>
                            <input id="Ren_Tot" name="Ren_Tot" type="text" class="form-control span" style="text-align: right;" readonly="" />
                        </div>
                    </div>
                </div>
                <div class="form-group reteTot">
                    <label class="col-xs-5 control-label label-xs"></label>
                    <div class="col-xs-7">
                        <div class="input-group input-group-sm">
                            <span class="input-group-addon bold alert-warning">A Pagar&nbsp;&nbsp;<i class="glyphicon glyphicon-arrow-right"></i></span>
                            <span class="input-group-addon bold alert-success"><i class="glyphicon glyphicon-usd"></i></span>
                            <input id="Val_Pcc" name="Val_Pcc" type="text" class="form-control bold span" style="text-align: right;font-size: 15px; background-color: white;" readonly="" />
                            <span id="infoLiquida" class="input-group-addon validate" style="display:none;"><i></i></span>
                            <span class="input-group-btn">
                                <button type="button" onclick="$('#retDetaDialog').dialog('open')" class="btn btn-info" title="Ver Detalle Retención" tabindex="-1"><span class="glyphicon glyphicon-eye-open"></span></button>
                            </span>
                        </div>
                    </div>
                </div>
            </form>
        </fieldset>
    </div>
    <div class="col-xs-6 gridProductosCalculo">
        <fieldset class="exa-fieldset">
            <legend class="Titulos2">Forma de Pago</legend>
            <form id="pagoFormTemp" action="javascript:" class="formDatos">
                <input type="text" name="Cpp_Cod" style="display: none;" />
                <div class="form-group pagoSri">
                    <label class="col-xs-2 control-label label-xs required">Pago&nbsp;SRI:</label>
                    <div class="col-xs-7">
                        <?php $rs_pag_sri = $obBD_con1->getArrayConsulta('tipopagocom.selectWhere', array('clean' => true, 'where' => array('Tpc_Est' => 'A')), $obBD_conexion); ?>
                        <select id="Tpc_Cod" name="Tpc_Cod" class="form-control input-xs readOnly">
                            <option value="">Seleccione...</option>
                            <?php foreach ($rs_pag_sri as $row) {
                                //echo "<option value='$row[Tpc_Cod]' >$row[Tpc_Sri] - $row[Tpc_Des]</option>";
                                echo "<option value='" . $row['Tpc_Cod'] . "' " . $selected .  " >" . mb_convert_encoding($row['Tpc_Sri'], 'UTF-8', 'ISO-8859-1') . " - " . mb_convert_encoding($row['Tpc_Des'], 'UTF-8', 'ISO-8859-1') . "</option>";
                            } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs required">Forma:</label>
                    <div class="col-xs-3">
                        <?php $rs_forma = $obBD_con1->getArrayConsulta('forma_pago.selectWhere', array('clean' => true, 'where' => array('For_Est' => 'A'), 'order' => 'For_Des'), $obBD_conexion); ?>
                        <select id="For_Cod" name="For_Cod" class="form-control input-xs readOnly" data-trigger="" onchange="checkCuentaPago();" required="">
                            <option value="">Seleccione...</option>
                            <?php foreach ($rs_forma as $row) {
                                echo "<option value='$row[For_Cod]' " . ($row['For_Des'] == 'Contado' ? "selected=''" : '') . ">$row[For_Des]</option>";
                            } ?>
                            <option value="3">Caja Chica</option>
                        </select>
                    </div>
                    <?php if ($configs['Cof_Con'] == 'S') { ?>
                        <label class="col-xs-2 control-label label-xs required">Cuenta:</label>
                        <div class="col-xs-5">
                            <select id="Pag_Pld" name="Pag_Pld" class="form-control input-xs readOnly" required=""></select>
                        </div>
                    <?php } ?>
                </div>
                <div class="form-group pagoCredito" style="display: none;">
                    <input type="text" name="Cpp_Min" style="display:none" />
                    <label class="col-xs-2 control-label label-xs required">Vencimiento:</label>
                    <div class="col-xs-3">
                        <input id="Cpp_Ven" name="Cpp_Ven" type="text" class="form-control input-xs datepickers" />
                    </div>
                    <label class="col-xs-2 control-label label-xs">Observación:</label>
                    <div class="col-xs-5">
                        <textarea name="Cpp_Obs" class="form-control input-xs"></textarea>
                    </div>
                </div>
            </form>
        </fieldset>
        <fieldset class="exa-fieldset" id="gridReembolsos">
            <legend class="Titulos2">Facturas a Reembolsar</legend>
            <div class="condensed">
                <table id="reembolsos"></table>
                <div id="reembolsosPager"></div>
            </div>
        </fieldset>
    </div>
</div>
<!-- DIALOGO DETALLE RETENCION -->
<div id="retDetaDialog" title="Retención">
    <div class="condensed-header">
        <table id="retencion"></table>
    </div>
</div>