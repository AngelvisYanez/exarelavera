<form id="frm_EditNego" name="frm_EditNego" class="form-horizontal normal" action="javascript:valDocUpdNeg();">
    <div class="col-xs-12">
        <fieldset class="exa-fieldset">
            <div class="row">
                <div class="col-md-6 col-sm-6">
                    <div class="form-group">
                        <input type="hidden" id="Cod_Neg" name="Cod_Neg" class="form-control input-xs" required>
                        <label class="col-xs-3 control-label label-xs">Fecha:</label>
                        <div class="col-xs-9">
                            <input id="Fec_Neg" name="Fec_Neg" type="date" value="<?php echo  date("Y-m-d") ?>" class="form-control input-xs">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-3 control-label label-xs">Tip.Nego:</label>
                        <div class="col-xs-9">
                            <select id="Tip_Neg" name="Tip_Neg" class="form-control input-xs" onchange="tipPagoNego(this.value);">
                                <option value="1">Con Anticipo (Crédito)</option>
                                <option value="2">Sin Anticipo (Contado)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-sm-6">
                    <div class="form-group">
                        <label class="col-xs-3 control-label label-xs">Nro.Nego:</label>
                        <div class="col-xs-9">
                            <input id="Num_Neg" name="Num_Neg" type="text" class="form-control input-xs" required readonly>
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>
    </div>
    <div class="col-xs-6">
        <fieldset class="exa-fieldset">
            <legend class="Titulos2">Productor:</legend>
            <div class="col-xs-12">
                <div class="form-group">
                    <input id="Prod_Cod" name="Prod_Cod" type="hidden" class="form-control input-xs" required>
                    <label class="col-xs-3 control-label label-xs required">Nombre:</label>
                    <div class="col-xs-9">
                        <div class="input-group input-group-xs">
                            <input id="Nom_Prod" name="Nom_Prod" type="text" class="form-control input-xs" required>
                            <span class="input-group-btn">
                                <button id="Prv_Btn" type="button" onclick="$('#prodDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Productor" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>
    </div>
    <div class="col-xs-6">
        <fieldset class="exa-fieldset">
            <legend class="Titulos2">Cliente:</legend>
            <div class="col-xs-12">
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs">Cliente:</label>
                    <div class="col-xs-9">
                        <select name="Empa_Cod" id="Empa_Cod" class="form-control input-xs">
                        </select>
                    </div>
                </div>
            </div>
        </fieldset>
    </div>
    <div class="col-xs-12">
        <fieldset class="exa-fieldset">
            <legend class="Titulos2">Sector:</legend>
            <div class="col-xs-6">
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs">Sector:</label>
                    <div class="col-xs-9">
                        <select id="Sec_Cod" name="Sec_Cod" class="form-control input-xs"></select>
                    </div>
                </div>
            </div>
            <div class="col-xs-6">
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs">Controlador:</label>
                    <div class="col-xs-9"><input name="Vnd_Name" id="Vnd_Name" type="text" class="form-control input-xs " value="<?php echo ($vendedor["vendedores"]); ?>" readonly></div>
                    <input type="text" name="Vnd_Cod" id="Vnd_Cod" value="<?php echo ($vendedor["Vnd_Cod"]); ?>" hidden>
                </div>
            </div>
        </fieldset>
    </div>
    <div class="col-xs-12">
        <fieldset class="exa-fieldset">
            <div class="col-xs-4">
                <div class="form-group">
                    <label class="col-xs-12 label-xs text-start">Libras Comprometidas:</label>
                    <div class="col-xs-12"><input id="Tot_Libras" name="Tot_Libras" type="number" step="any" class="form-control input-xs" placeholder="0.00"></div>
                </div>
            </div>
            <div class="col-xs-4">
                <div class="form-group">
                    <label class="col-xs-12 label-xs">Comisión:</label>
                    <div class="col-xs-12">
                        <input id="Prec_Comis" name="Prec_Comis" type="number" step="any" class="form-control input-xs" placeholder="0.00">
                    </div>
                </div>
            </div>
            <div class="col-xs-4">
                <div class="form-group">
                    <label class="col-xs-12 label-xs">Anticipo:</label>
                    <div class="col-xs-12"><input id="Val_Ant" name="Val_Ant" type="number" step="any" class="form-control input-xs" placeholder="0.00"></div>
                </div>
            </div>
            <div class="col-xs-12"><br>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs">Seleccionar Aguaje:</label>
                    <div class="col-xs-9"><select name="Cod_Agu" id="Cod_Agu" class="form-control input-xs"> </select>
                    </div>
                </div>
            </div>
            <div class="col-xs-12">
                <div class="form-group  col-md-4">
                    <label class="control-label label-xs required">Tipo:</label>
                    <div class="col-xs-12">
                        <label class="radio-inline">
                            <input name="Clasf" id="tipCla" type="radio" value="CLASIFICACION">Clasificación
                        </label>
                        <label class="radio-inline">
                            <input name="Clasf" id="tipBar" type="radio" value="BARRER"> Barrer
                        </label>
                    </div>
                </div>
                <br>
                <div class="form-group col-md-4">
                    <div class="col-xs-12">
                        <div class="input-group input-group-xs">
                            <input name="Est_Neg" id="EstCose" type="checkbox" value="C"> Cosechado
                        </div>
                    </div>
                </div>
                <div class="form-group  col-md-4">
                    <label class="control-label label-xs required">Fecha pesca:</label>
                    <div class="col-xs-12">
                        <input id="Fec_Pesca" name="Fec_Pesca" type="date" value="<?php echo  date("Y-m-d") ?>" class="form-control input-xs">
                    </div>
                </div>
            </div>
        </fieldset>
    </div>
    <div class="col-md-12" style="margin-top: 15px;">
        <div class="center">
            <button type="button" class="btn btn-sm btn-danger no" onclick="canceEditNego();"><i class="glyphicon glyphicon-minus"></i> Cancelar</button>
            <button type="button" id="btn_update_nego" class="btn btn-sm btn-primary" onclick="$('#frm_EditNego').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
        </div>
    </div>
</form>