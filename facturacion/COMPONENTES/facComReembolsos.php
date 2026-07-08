<?php

/* 
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */

?>
    <div id="comprasReembolsoDialog" title="Agregar factura para Reembolso" style="display:none;">  
        <form class="form-horizontal normal" id="comprasReembolsoForm" action="javascript:if(validaNoIdentif($('#Rem_Ced').val())['success']){ AgregaReembolso(); }else{ /*$('#Rem_Ced').flyout('show').focus()*/ }">                     
            <fieldset class="exa-fieldset" >
                <legend class="Titulos2">Datos del Documento</legend>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Cédula/RUC:</label>  
                    <div class="col-xs-5" >
                        <div class="input-group input-group-xs">                                          
                            <input id="Rem_Ced" name="Rem_Ced" type="text" class="form-control input-xs" onchange="if(validaNoIdentif(this.value)['success']){ $('#Rem_Ide').val(this.value.length===10?2:1); $(this).fieldValid(true); }else{ $('#Rem_Ide').val('');  $(this).fieldValid(false,validaNoIdentif(this.value)['message']); };" required="" />
                            <span class="input-group-addon validate" ><i></i></span>
                        </div>
                    </div> 
                </div> 
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs">Identificación:</label>  
                    <div class="col-xs-5" >                          
                        <select name="Rem_Ide" id="Rem_Ide" class="form-control input-xs readOnly" >
                            <option value=""></option>
                            <?php foreach($rs_identi as $row){ /*if($row['Ide_Prc']=='1'||$row['Ide_Prc']=='2')*/ echo "<option value='$row[Ide_Cod]'>$row[Ide_Des]</option>"; } ?>
                        </select>
                    </div>
                </div> 
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs">Documento:</label>  
                    <div class="col-xs-9" >                          
                        <select id="Rem_Tic" name="Rem_Tic" class="form-control input-xs" onchange="" required="" >
                            <option value="">Seleccione...</option>
                           <?php foreach($rs_tip_compr as $row){ 
                               //if($row['Tic_Sri']==1||$row['Tic_Sri']==2||$row['Tic_Sri']==4||$row['Tic_Sri']==5||$row['Tic_Sri']==8)
                               if($row['Tic_Sri']!=0)
                               //echo "<option value='$row[Tic_Sri]'>$row[Tic_Sri] - $row[Tic_Des]</option>";
                               echo "<option value='{$row['Tic_Sri']}'>" . mb_convert_encoding($row['Tic_Sri'], 'UTF-8', 'ISO-8859-1') . " - " . mb_convert_encoding($row['Tic_Des'], 'UTF-8', 'ISO-8859-1') . "</option>";

                            } ?>
                        </select>
                    </div>
                </div> 
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Número:</label>                                     
                    <div class="col-xs-6" >                                                        
                        <input type="text" id="Rem_Num" name="Rem_Num" onchange="" class="form-control input-xs" required="" />                        
                    </div>
                </div> 
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Autoriza:</label>  
                    <div class="col-xs-9" >
                        <div class="input-group input-group-xs"> 
                            <input id="Rem_Aut" type="text" name="Rem_Aut" class="form-control datatitle" onkeypress="return validar_numeric(event);" required="" maxlength="49" pattern="[0-9]{10}||[0-9]{37}||[0-9]{49}" />
                            <span class="input-group-addon validate" ><i></i></span>
                        </div>
                    </div> 
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Emision:</label>  
                    <div class="col-xs-5">
                      <div class="input-group">                                          
                          <input id="Rem_Fec" name="Rem_Fec" type="text" class="form-control input-xs datepickers" required="" />
                          <span class="input-group-addon input-xs" title="Fecha de Emisión del Proveedor"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                      </div>
                    </div>    
                </div>
            </fieldset>
            <fieldset class="exa-fieldset" >
                <legend class="Titulos2">Valores</legend>
                <div class="form-group">
                    <label class="col-xs-4 control-label label-xs required">Tarifa Iva 0%:</label>  
                    <div class="col-xs-4">                            
                        <input name="Rem_Niv" type="text" class="form-control input-xs txtRight" required="" onkeypress="return validar_decimal(event);" />                          
                    </div>    
                </div>
                <div class="form-group">
                    <label class="col-xs-4 control-label label-xs required">Tarifa Iva No 0%:</label>  
                    <div class="col-xs-4">                            
                        <input name="Rem_Siv" type="text" class="form-control input-xs txtRight" required="" onkeypress="return validar_decimal(event);" />                          
                    </div>    
                </div>
                <div class="form-group">
                    <label class="col-xs-4 control-label label-xs required">Tarifa No Obj. Iva:</label>  
                    <div class="col-xs-4">                            
                        <input name="Rem_Oiv" type="text" class="form-control input-xs txtRight" required="" onkeypress="return validar_decimal(event);" />                          
                    </div>    
                </div>
                <div class="form-group">
                    <label class="col-xs-4 control-label label-xs required">Base Excenta de Iva:</label>  
                    <div class="col-xs-4">                            
                        <input name="Rem_Eiv" type="text" class="form-control input-xs txtRight" required="" onkeypress="return validar_decimal(event);" />                          
                    </div>    
                </div>
                <hr />
                <div class="form-group">
                    <label class="col-xs-4 control-label label-xs required">Monto de Iva:</label>  
                    <div class="col-xs-4">                            
                        <input name="Rem_Iva" type="text" class="form-control input-xs txtRight" required="" onkeypress="return validar_decimal(event);" />                          
                    </div>    
                </div>
                <div class="form-group">
                    <label class="col-xs-4 control-label label-xs required">Monto de Ice:</label>  
                    <div class="col-xs-4">                            
                        <input name="Rem_Ice" type="text" class="form-control input-xs txtRight" required="" onkeypress="return validar_decimal(event);" />                          
                    </div>    
                </div>
                
            </fieldset>  
            <div class="form-group center">
                <button type="submit" class="btn btn-xs btn-success"><i class="glyphicon glyphicon-plus"></i> Agregar Reembolso</button>
            </div>
        </form>    
    </div>  
