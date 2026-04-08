<form id="frm_negociacion" name="frm_negociacion" class="form-horizontal normal" action="javascript:validaDocument();">
    <div class="col-xs-12">
        <fieldset class="exa-fieldset">
            <div class="row">
                <div class="col-md-6 col-sm-6">
                    <div class="form-group">
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
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs">Contacto:</label>
                    <div class="col-xs-9"><input id="Telf_Prod" name="Telf_Prod" type="text" class="form-control input-xs" readonly></div>
                </div>
            </div>
        </fieldset>
    </div>
    <div class="col-xs-6">
        <fieldset class="exa-fieldset">
            <legend class="Titulos2">Sector:</legend>
            <div class="col-xs-12">
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs">Sector:</label>
                    <div class="col-xs-9">
                        <select id="Sec_Cod" name="Sec_Cod" class="form-control input-xs"></select>
                    </div>
                </div>
            </div>
        </fieldset>
    </div>
    <div class="col-xs-12">
        <fieldset class="exa-fieldset">
            <legend class="Titulos2">Garantia:</legend>
            <div class="col-xs-4">
                <div class="form-group">
                    <label class="col-xs-4 control-label label-xs">Valor garantia:</label>
                    <div class="col-xs-8">
                        <input id="Val_garantia" name="Val_garantia" type="number" step="any" class="form-control input-xs" placeholder="0.00">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-4 control-label label-xs">Garantia neta:</label>
                    <div class="col-xs-8"><input id="Val_Grnt_Neta" name="Val_Grnt_Neta" type="number" step="any" class="form-control input-xs" placeholder="0.00"></div>
                </div>
            </div>
            <div class="col-xs-5">
                <div class="form-group">
                    <label class="col-xs-4 control-label label-xs">Anticipo:</label>
                    <div class="col-xs-8"><input id="Val_Ant" name="Val_Ant" type="number" step="any" class="form-control input-xs" placeholder="0.00"></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-4 control-label label-xs">Balanceado:</label>
                    <div class="col-xs-8"><input id="Val_Balanceado" name="Val_Balanceado" type="number" step="any" class="form-control input-xs" placeholder="0.00"></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-4 control-label label-xs">Larva:</label>
                    <div class="col-xs-8"><input id="Val_Larva" name="Val_Larva" type="number" step="any" class="form-control input-xs" placeholder="0.00"></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-4 control-label label-xs">Total:</label>
                    <div class="col-xs-8"><input id="Neg_Tot" name="Neg_Tot" type="number" step="any" class="form-control input-xs" placeholder="0.00"></div>
                </div>
            </div>
            <div class="col-xs-3">
                <div class="form-group">
                    <label class="col-xs-4 control-label label-xs">Libras:</label>
                    <div class="col-xs-8"><input id="Tot_Libras" name="Tot_Libras" type="number" step="any" class="form-control input-xs" placeholder="0.00"></div>
                </div>
            </div>

        </fieldset>
    </div>
    <div class="form-group col-xs-12">
        <div class="form-group">
            <label class="col-xs-3 control-label label-xs">Link contrato:</label>
            <div class="col-xs-9"> <input id="Link_Contrato" name="link_Contrato" type="text" class="form-control input-xs" placeholder="Link"></div>
        </div>
        <div class="form-group" id="link_garan">
            <label class="col-xs-3 control-label label-xs">Link Garantia:</label>
            <div class="col-xs-9"> <input id="Link_Garantia" name="Link_Garantia" type="text" class="form-control input-xs" placeholder="Link"></div>
        </div>
        <div class="form-group" id="linkVerfGaran">
            <label class="col-xs-3 control-label label-xs">Link Verificación garantia:</label>
            <div class="col-xs-9"> <input id="Link_Verf_Garantia" name="Link_Verf_Garantia" type="text" class="form-control input-xs" placeholder="Link"></div>
        </div>
        <div class="form-group">
            <label class="col-xs-3 control-label label-xs">Nota:</label>
            <div class="col-xs-9">
                <textarea id="nota" name="nota" class="form-control input-xs" placeholder="Descripción"></textarea>
            </div>
        </div>
    </div>
    <div class="col-md-12" style="margin-top: 15px;">
        <div class="center" >
            <button type="button" class="btn btn-sm btn-danger no" onclick="cancelarNegociacion();"><i class="glyphicon glyphicon-minus"  ></i> Cancelar</button>
            <button type="button" class="btn btn-sm btn-primary" onclick="$('#frm_negociacion').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
        </div>
    </div>
</form>
