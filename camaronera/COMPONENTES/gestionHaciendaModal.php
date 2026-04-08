<?php
/* 
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */
?>
<div id="haciCreateDialog" title="Gestion Hacienda" style="display:none;">
    <form class="form-horizontal normal" id="haciCreateForm" action="javascript:saveHacienda();">
        <!--input type="text" name="Index" class="form-control input-xs hidden" value="" />
        <input type="text" name="Prh_Cod" class="form-control input-xs hidden" value="" /-->
        <fieldset class="exa-fieldset">
            <legend class="Titulos2">Datos del Sector</legend>
            <div class="row">
                <div class="col-xs-6">
                    <div class="form-group">
                        <label class="col-xs-3 control-label label-xs required">Nombre:</label>
                        <div class="col-xs-9">
                            <input type="text" name="Sec_Nom" class="form-control input-xs" value="" required="" />
                        </div>
                    </div>
                    <div class="help-block"></div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs">Dirección:</label>
                    </div>
                    <div class="form-group">
                        <div class="col-xs-12">
                            <textarea name="Sec_Dir" class="form-control input-xs" value="" /></textarea>
                        </div>
                    </div>
                </div>
                <div class="col-xs-6">
                    <div class="form-group magap propio">
                        <label class="col-xs-3 control-label label-xs">Encargado</label>
                        <div class="col-xs-9">
                            <input type="text" name="Sec_Encargado" class="form-control input-xs" value="" />
                        </div>
                    </div>

                    <div class="help-block"></div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs">Descripción:</label>
                    </div>
                    <div class="form-group">
                        <div class="col-xs-12">
                            <textarea name="Sec_Desc" class="form-control input-xs" value="" /></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>
        <div class="center">
            <button type="submit" class="btn btn-sm btn-success"><i class="glyphicon glyphicon-plus"></i> Guardar</button>
        </div>
    </form>
</div>