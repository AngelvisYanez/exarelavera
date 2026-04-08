<form id="frm_precios" name="frm_precios" class="form-horizontal normal" action="javascript:validaPreciosAguaje();">
    <div class="col-xs-12">
        <fieldset class="exa-fieldset">
            <div class="row">
                <div class="col-md-6 col-sm-6">
                    <div class="form-group">
                            <label class="col-sm-3 control-label label-xs">Búsqueda:</label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <input type="text" id="search" name="search" class="form-control input-xs" placeholder="Número,  Nombre" autofocus="">
                                    <span class="input-group-btn">
                                        <button class="btn btn-success btn-xs" type="button" title="Buscar aguaje" onclick="searchAguaje()"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                    </span>
                                </div>
                            </div>
                       
                    </div>
                </div>
                <div class="col-md-6 col-sm-6">
                    <div class="text-right">
                        <button type="button" class="btn btn-success btn-xs " onclick="agregarAguajesAddDialog('add')"> <i class="fa fa-plus"></i> Nuevo</button>
                    </div>
                </div>
            </div>
        </fieldset>
    </div>
    <div class="col-xs-12">
        <table id="containerAguajes" style="width: 100%!important;"></table>
    </div>
    <hr>
    <div class="col-xs-12">
        <fieldset class="exa-fieldset">
            <div class="row">
                <div class="col-md-6 col-sm-6">
                    <div class="form-group">
                        <label class="col-xs-4 label-xs">Aguaje seleccionado:</label>
                        <div class="col-xs-8">
                            <input type="text" name="Nom_Agu" id="Nom_Agu" class="form-control input-xs" readonly>
                            <input type="text" name="Agu_Cod" id="Agu_Cod" hidden>
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>
    </div>
    <div class="col-xs-12">
        <div class="row">
            <div class="col-md-3">
                <label for="">Tipo: Entero</label>
                <table id="tallEntero"></table>
            </div>
            <div class="col-md-3">
                <label for="">Tipo: COLA(A)</label>
                <table id="tallTipA"></table>
            </div>
            <div class="col-md-3">
                <label for="">Tipo: COLA(B)</label>
                <table id="tallTipB"></table>
            </div>
            <div class="col-md-3">
                <label for="">Tipo: NACIONAL</label>
                <table id="tallTipNac"></table>
            </div>
        </div>
    </div>
    <div class="col-md-12 mt-3" style="margin-top: 15px;">
        <div class="form-group">
            <div class="center">
                <button type="button" class="btn btn-sm btn-danger no" onclick="$('#aguajesDialog').dialog('close');"><i class="glyphicon glyphicon-minus"></i>Cancelar</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="$('#frm_precios').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
            </div>
        </div>
    </div>
</form>