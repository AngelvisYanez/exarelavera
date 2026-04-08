<?php

/* 
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */
?>
    <div id="haciCreateDialog" title="Gestion Hacienda" style="display:none;">  
        <form class="form-horizontal normal" id="haciCreateForm" action="javascript:saveHacienda();">
            <input type="text" name="Index" class="form-control input-xs hidden" value=""  />
            <input type="text" name="Prh_Cod" class="form-control input-xs hidden" value=""  />
            <fieldset class="exa-fieldset" >
                <legend class="Titulos2">Datos de la Hacienda</legend>
                <div class="row">                    
                    <div class="col-xs-7">
                    <div class="form-group">
                        <label class="col-xs-3 control-label label-xs required">Nombre:</label>  
                        <div class="col-xs-9">
                            <input type="text" name="Prh_Nom" class="form-control input-xs" value="" required="" />
                        </div> 
                    </div><div class="help-block"></div>
                    <div class="form-group">
                        <label class="col-xs-8 control-label label-xs">Posee&nbsp;Magap:</label>  
                        <div class="col-xs-4">
                            <select id="MagapCod" onchange="setMagap();" class="form-control input-xs datatrigger">
                                <option value="S" checked="">SI</option>
                                <option value="N">NO</option>
                            </select>
                        </div> 
                    </div>
                    <div class="form-group magap propio">
                        <label class="col-xs-3 control-label label-xs">Cod.&nbsp;Magap:</label>  
                        <div class="col-xs-9">
                            <input type="text" name="Prh_Mag" class="form-control input-xs" value="" />
                        </div> 
                    </div>
                    <div class="form-group magap propio">
                        <label class="col-xs-3 control-label label-xs">Inscr.&nbsp;Magap:</label>  
                        <div class="col-xs-9">
                            <input type="text" name="Prh_Inm" class="form-control input-xs" value="" />
                        </div> 
                    </div> 
                    <div class="form-group magap prestado" style="display: none">
                        <label class="col-xs-3 control-label label-xs">Tit.&nbsp;Magap:</label>  
                        <div class="col-xs-9">
                            <div class="input-group input-group-xs">                                
                                <input type="text" name="Prh_Nal" class="form-control input-xs" value="" />
                                <span class="input-group-addon" title="Nombre del Dueño del Magap Prestado"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                            </div>    
                        </div> 
                    </div>
                    <div class="form-group magap prestado" style="display: none">
                        <label class="col-xs-3 control-label label-xs">Cod.&nbsp;Magap:</label>  
                        <div class="col-xs-9">
                            <div class="input-group input-group-xs">            
                                <input type="text" name="Prh_Mal" class="form-control input-xs" value="" />
                                <span class="input-group-addon" title="Codigo del Magap Prestado"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                            </div>  
                        </div> 
                    </div>
                    </div>   
                    <div class="col-xs-5">
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs">Hectareas:</label>  
                        <div class="col-xs-8">
                            <input type="text" name="Prh_Hec" class="form-control input-xs" value="" onkeypress="return validar_decimal(event);" />
                        </div> 
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs">Cupo:</label>  
                        <div class="col-xs-8">
                            <input type="text" name="Prh_Cup" class="form-control input-xs" value="" onkeypress="return validar_numeric(event);" />
                        </div> 
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs">Dirección:</label> 
                    </div>
                        <div class="form-group">
                            <div class="col-xs-12">
                                <textarea name="Prh_Dir" class="form-control input-xs" value="" /></textarea>
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