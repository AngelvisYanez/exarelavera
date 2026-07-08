<?php

/* 
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */
?>
<form id="serachDocDorm" class="form-horizontal normal" action="javascript:$('#searchGrid').Search('#serachDocDorm','searchDocument');">
    <div class="row">
        <div class="col-xs-6">
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">B&uacute;squeda</legend>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Filtros:</label>
                    <div class="col-xs-10 radioset opt_search">
                        <input id="radsc1" name="op_opciones" type="radio" value="p" checked="" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc1">&nbsp;Proveedor&nbsp;</label>
                        <input id="radsc2" name="op_opciones" type="radio" value="c" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc2"> C&eacute;dula/RUC </label>
                        <input id="radsc3" name="op_opciones" type="radio" value="d" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc3"> No. Documento </label>
                        <input id="radsc4" name="op_opciones" type="radio" value="r" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc4"> No. Retencion </label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">B&uacute;squeda:</label>
                    <div class="col-xs-7">
                        <div class="input-group">
                            <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus class="form-control input-sm clearable submit" />
                            <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Documento" tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                        </div><!-- /input-group -->
                    </div><input type="text" tabindex="-1" style="display:none;" />
                </div>
            </fieldset>
        </div>
        <div class="col-xs-6">
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Filtros</legend>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Documento:</label>
                    <div class="col-xs-10">
                        <select name="Tic_Cod" class="form-control input-xs">
                            <option value="">-- TODO --</option>
                            <?php foreach ($rs_tip_compr as $row) {
                                if (isset($allTypes) || ($row['Tic_Sri'] != 4 && $row['Tic_Sri'] != 5 && $row['Tic_Sri'] != 7 && $row['Tic_Sri'] != 23 && $row['Tic_Sri'] != 24))
                                    // echo "<option value='$row[Tic_Cod]' data-ticsri='$row[Tic_Sri]'>$row[Tic_Sri] - $row[Tic_Des]</option>";
                                    echo "<option value='{$row['Tic_Cod']}' data-ticsri='{$row['Tic_Sri']}'>" . mb_convert_encoding($row['Tic_Sri'], 'UTF-8', 'ISO-8859-1') . " - " . mb_convert_encoding($row['Tic_Des'], 'UTF-8', 'ISO-8859-1') . "</option>";
                            } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Periodo:</label>
                    <div class="col-xs-2">
                        <select name="Pec_Cod" class="form-control input-xs search_pec" onchange="if(this.value==='') $('#Cmb_Mes').attr('disabled','disabled'); else $('#Cmb_Mes').removeAttr('disabled'); ">
                            <?php foreach ($rs_periodo as $row) {
                                echo "<option value='$row[Pec_Cod]'>$row[Periodo]</option>";
                            } ?>
                            <option value="">
                                << TODOS>>
                            </option>
                        </select>
                    </div>
                    <label class="col-xs-2 control-label label-xs">Mes:</label>
                    <div class="col-xs-2">
                        <select id="Cmb_Mes" name="Cmb_Mes" class="form-control input-xs search_pec">
                            <option value="">
                                << TODOS>>
                            </option>
                            <?Php for ($i = 1; $i <= 12; $i++) { ?><option <?php if ($i == $mes) {
                                                                            echo "selected=''";
                                                                        } ?> value="<?Php echo $i; ?>"><?php echo mes($i, 1); ?></option><?Php } ?>
                        </select>
                    </div>

                    <label class="col-xs-2 control-label label-xs">Mis Ingresos</label>
                    <div class="col-xs-2">
                        <input type="checkbox" value="S" offval="N" id="mis_ingresos" name="mis_ingresos">
                    </div>

                </div>
            </fieldset>
        </div>
    </div>
</form>
<div style="min-height: 270px;">
    <table id="searchGrid"></table>
    <table id="searchGridPager"></table>
    <div class="Titulos2"><span id="plan-footer"><strong>Leyenda:</strong> <span class="glyphicon glyphicon-stop green"></span> Contiene Pagos | <span class="glyphicon glyphicon-remove red"></span> Anulados/Inactivos | <span class="fa fa-creative-commons purple"></span> Reposición Caja Chica | <span class="fa fa-globe green"></span> Retención Electronica Validada | <span class="glyphicon glyphicon-lock orange"></span> Formato Anterior</div>
</div>
<!-- DIALOGO DETALLE DOCUMENTO -->
<div id="docDetaDialog" title="Documento">
    <fieldset class="exa-fieldset">
        <legend class="Titulos2">Documento:</legend>
        <div class="form-horizontal normal" style="padding: 0 4px;">
            <div class="form-group">
                <label class="col-xs-2 control-label label-xs">Cédula/RUC:</label>
                <div class="col-xs-4"><span name="Prs_Ced" class="form-control input-xs"></span></div>
                <label class="col-xs-2 control-label label-xs">Doc.Num.:</label>
                <div class="col-xs-4"><span name="Cop_Num" class="form-control input-xs"></span></div>
            </div>
            <div class="form-group">
                <label class="col-xs-2 control-label label-xs">Proveedor:</label>
                <div class="col-xs-6"><span name="proveedor" class="form-control input-xs"></span></div>
                <label class="col-xs-1 control-label label-xs">Fecha:</label>
                <div class="col-xs-3"><span name="Cop_Fec" class="form-control input-xs"></span></div>
            </div>
            <div class="form-group condensed">
                <div class="col-xs-12">
                    <div class="pull-right">
                        <table id="detaDocu"></table>
                    </div>
                </div>
                <div class="col-xs-12" style="text-align: right;font-size: 8px;padding-top: 2px;"><b>CREACIÓN:</b> <span name="Cop_Sys" class="databind"></span> &nbsp;&nbsp;-&nbsp;&nbsp; <b>USUARIO:</b> <span name="vendedor" class="databind"></span></div>
            </div>
        </div>
    </fieldset>
    <fieldset class="exa-fieldset" id="retViewGrid">
        <legend class="Titulos2">Retención:</legend>
        <div class="form-horizontal normal" style="padding: 0 4px;">
            <div class="form-group">
                <label class="col-xs-2 control-label label-xs">Num.:</label>
                <div class="col-xs-3"><span name="Ret_Num" class="form-control input-xs"></span></div>
                <label class="col-xs-1 control-label label-xs">Fecha:</label>
                <div class="col-xs-3"><span name="Ret_Fec" class="form-control input-xs"></span></div>
                <label class="col-xs-2 control-label label-xs">Autorización.:</label>
                <div class="col-xs-1"><span name="Ret_Aut" class="form-control input-xs"></span></div>
            </div>
            <div class="form-group condensed">
                <div class="col-xs-12">
                    <div class="pull-right">
                        <table id="detaRete"></table>
                    </div>
                </div>
            </div>
        </div>
    </fieldset>
</div>

<!-- DIALOGO OBSERVACION -->
<div id="docDetaObservacion" title="Documento">
    <fieldset class="exa-fieldset">
        <legend class="Titulos2">Observacion:</legend>
        <div class="form-horizontal normal" style="padding: 0 4px;">
            <div class="form-group">
                <input type="text" id="Cop_Codigo" name="Cop_Cod" style="display: none;">
                <textarea class="form-control" id="Cop_Observacion" name="Cop_Obs" rows="5" style="resize: none"></textarea>
                <br>
                <div class="col text-center">
                    <button id='btnEditarObservacion' class="btn-sm btn-success">Guardar </button>
                </div>
            </div>
        </div>
    </fieldset>
</div>