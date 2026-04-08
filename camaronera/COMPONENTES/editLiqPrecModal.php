<form id="frm_det_liq" name="frm_det_liq" class="form-horizontal normal" action="javascript:validaDocEditLiq();">
    <div class="col-xs-12">
        <fieldset class="exa-fieldset">
            <div class="row">
                <div class="col-md-12 col-sm-12">
                    <div class="form-group">
                        <label class="col-xs-2 control-label label-xs">Cod.Liq:</label>
                        <div class="col-xs-4">
                            <input type="text" name="Num_Liq" id="Num_Liq" class="form-control input-xs " readonly>
                            <input type="text" id="Liq_Cod" name="Liq_Cod" hidden>
                            <input type="text" id="Cod_Agu" name="Cod_Agu" hidden>
                          </div>
                        <label class="col-xs-2 control-label label-xs">Num.Neg:</label>
                        <div class="col-xs-4">
                            <input type="text" id="Num_Neg" name="Num_Neg" class="form-control input-xs " readonly>
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>
    </div>
    <div class="col-xs-12">
        <fieldset class="exa-fieldset">
            <legend class="Titulos2">Tallas camarón:</legend>
            <div class="col-xs-12">
                <div class="form-group  col-md-4">
                    <input type="hidden" name="Prod_Cod" id="Prod_Cod" class="form-control input-xs">
                    <label class="control-label label-xs required">Tipo:</label>
                    <div class="col-xs-12">
                        <label class="radio-inline">
                            <input name="clasf" id="tipCla" type="radio" value="CLASIFICACION" checked> Clasificación
                        </label>
                        <label class="radio-inline">
                            <input name="clasf" id="tipBar" type="radio" value="BARRER"> Barrer
                        </label>
                    </div>

                </div>
                <div class="form-group col-md-4">
                    <label class="control-label label-xs required">Medidas:</label>
                    <div class="col-xs-12">
                        <div cclass="radio-inline">
                            <input name="medTip" id="tipkls" type="radio" value="kilos" checked> Por Kilos
                        </div>
                        <div class="radio-inline">
                            <input name="medTip" id="tipLib" type="radio" value="libras"> Por libras
                        </div>
                    </div>
                </div>
                <div class="form-group col-md-4">
                    <label class="control-label label-xs required">Tipo de empaque:</label>
                    <div class="col-xs-12">
                        <div class="input-group input-group-xs">
                            <input name="tipPaq" id="tipEnt" type="radio" value="ENTERO"> Entero
                        </div>
                        <div class="input-group input-group-xs">
                            <input name="tipPaq" id="tipColA" type="radio" value="COLAA"> Cola A
                        </div>
                        <div class="input-group input-group-xs">
                            <input name="tipPaq" id="tipColB" type="radio" value="COLAB"> Cola B
                        </div>
                        <div class="input-group input-group-xs">
                            <input name="tipPaq" id="tipColN" type="radio" value="NACIONAL"> Nacional
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>
    </div>
    <div class="col-xs-12">
        <fieldset class="exa-fieldset">
            <legend class="Titulos2">Agregar items:</legend>
            <div class="col-xs-12">
                <div class="form-group col-xs-5">
                    <label class="col-xs-4  label-xs">Tallas:</label>
                    <div class="col-xs-8">
                        <select name="Cod_Prec" id="Cod_Prec" type="text" class="form-control input-xs "></select>
                    </div>
                </div>
                <div class="form-group col-xs-5">
                    <label class="col-xs-4  label-xs">Cantidad:</label>
                    <div class="col-xs-8">
                        <input type="number" id="cant" step="any" name="cant" class="form-control input-xs">
                    </div>
                </div>
                <div class="form-group col-md-3">
                    <button class="form-control input-xs btn btn-sm btn-success saveDetLiq"><i class="fa fa-plus"></i> Agregar</button>
                </div>
            </div>
        </fieldset>
    </div>
    <div class="col-xs-12">
        <div style="width: 100%;">
            <table id="tablasPrecLiq"></table>
            <div id="containerPrecLiq"></div>
        </div>
    </div>

    <div class="col-xs-12" style="margin-top: 10px;">
        <div class="center">
            <button type="button" class="btn btn-sm btn-danger no" onclick="cancelarEditLiq();"><i class="glyphicon glyphicon-minus"  ></i> Cancelar</button>
            <button type="button" class="btn btn-sm btn-primary" onclick="$('#frm_det_liq').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
        </div>
    </div>
</form>