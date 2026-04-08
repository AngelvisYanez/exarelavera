<?php
/* 
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */?>
                <div class="col-sm-5">   
                    <form id="formDocumento" action="javascript:validaDocument();" class="form-horizontal normal">                    
                    <fieldset class="exa-fieldset" id="provFormTemp">
                        <legend class="Titulos2">Datos del Proveedor</legend>
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs">Cédula/RUC:</label>  
                            <div class="col-xs-6" >
                              <input name="Prs_Cod" data-name="Prs_Cod" type="text" style="display:none;" />
                              <input name="Prv_Cod" data-name="Prv_Cod" type="text" style="display:none;" />
                              <input name="op_opciones" data-name="op_opciones" type="text" value="c" style="display: none;">  
                              <div class="input-group input-group-xs">                                          
                                <input name="search" data-name="Prs_Ced" onkeydown="if (event.keyCode === 13){ $.SearchOrDialog('#provDialog',selectProvee); }" type="text" placeholder="Ingrese Proveedor..."  class="form-control input-xs clearable dialogSearch" tabindex="1" />
                                <span class="input-group-btn">
                                    <button id="Prv_Btn" type="button" onclick="$('#provDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Proveedor"  tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                    <!--<button type="button" onclick="$('#provCreateForm').setData({Prv_Esp:'N',Prv_Con:'N'}).find('.validate').find('i').removeAttr('class'); $('#provCreateDialog').dialog('open'); $('#reset').val(1); " class="btn btn-success btn-xs" title="Registrar Proveedor"  tabindex="2"><span class="glyphicon glyphicon-plus"></span></button>-->    
                                </span>
                              </div>
                            </div>                                      
                            <label class="col-xs-4 control-label label-xs">Oblig.Contab:&nbsp;<i id="Prv_Con" class="blue glyphicon glyphicon-remove" style="font-size: 12px;"></i></label>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs required">Proveedor:</label>  
                            <div class="col-xs-6" >
                                <span name="Proveedor" data-name="Proveedor" class="form-control input-xs databind datatitle"></span>
                            </div>                                        
                            <label class="col-xs-4 control-label label-xs">Contr.Especial:&nbsp;<i  id="Prv_Esp" class="blue glyphicon glyphicon-remove" style="font-size: 12px;"></i></label> 
                        </div>
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs">Dirección:</label>  
                            <div class="col-xs-10" >
                                <div class="input-group input-group-xs">
                                    <input name="Prs_Dir" data-name="Prs_Dir" type="text" class="form-control span datatitle" readonly="" tabindex="-1">
                                    <span class="input-group-addon bold">e-mail:</span>
                                    <input name="Prs_Cor" data-name="Prs_Cor" type="text" class="form-control span datatitle" readonly="" tabindex="-1" />												
                                </div>											
                            </div>                    
                        </div>
                        
                    </fieldset>   
                    <fieldset class="exa-fieldset" id="retForm">
                        <legend class="Titulos2">Datos de la Retención</legend>
                        <input name="Ret_Cod" type="text" class="hidden" />
                        <input type="text" id="Aut_Cod_Old" name="Aut_Cod_Old" class="hidden" value="<?php echo isset($row_rs_autorizaci)?$row_rs_autorizaci[0]['Aut_Cod']:''; ?>" />
                        <input type="text" id="Aut_Cod" name="Aut_Cod" class="hidden" value="<?php echo isset($row_rs_autorizaci)?$row_rs_autorizaci[0]['Aut_Cod']:''; ?>" />
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs required">Documento:</label>  
                            <div class="col-xs-8" >
                                <?php $rs_tip_compr = $obBD_con1->getArrayConsulta('tipo_compr.selectWhere', array('Tic_Est'=>'A', 'Tic_Sri'=>7), $obBD_conexion); ?>
                                <select id="Tic_Cod" name="Tic_Cod" class="form-control input-xs readOnly" tabindex="4" disabled="" required="" >                                    
                                    <?php foreach($rs_tip_compr as $row){                                        
                                       echo "<option value='$row[Tic_Cod]' data--tic_-sri='$row[Tic_Sri]'>$row[Tic_Des]</option>";
                                    } ?>
                                </select>
                            </div>  
                            <label class="col-xs-2 control-label label-xs">Cód.Int.:&nbsp;<span id="Aut_Cod" class="blue"><?php echo isset($row_rs_autorizaci)?$row_rs_autorizaci[0]['Aut_Cod']:''; ?></span></label> 
                        </div>
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs required">Numero:</label> 
                            <div class="col-xs-4" >
                                <input type="text" id="Aut_Tem" name="Aut_Tem" style="display:none;" value="<?php echo isset($row_rs_autorizaci)?$row_rs_autorizaci[0]['Aut_Tem']:''; ?>" />
                                <div class="input-group input-group-xs">                                          
                                    <input id="Ret_Num" name="Ret_Num" type="text" class="form-control input-xs readOnly ret_field" onchange="validaRetNum()" required="" value="" />
                                    <span class="input-group-addon validate ret_num"><i></i></span>                                    
                                </div>
                            </div>  
                            <label class="col-xs-2 control-label label-xs required">Autoriza:</label>  
                            <div class="col-xs-4" ><span id="Aut_Sri" name="Aut_Sri" class="form-control input-xs databind"><?php echo isset($row_rs_autorizaci)?($row_rs_autorizaci[0]['Aut_Tem']=='E'?'Electronica':$row_rs_autorizaci[0]['Aut_Sri']):''; ?></span></div>
                            

                        </div>
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs required">Periodo:</label>  
                            <div class="col-xs-4" >
                                <select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs datatrigger" onchange="if(this.value==='') $('#Cmb_Mes').attr('disabled','disabled'); else $('#Cmb_Mes').removeAttr('disabled'); ">
                                    <?php if(isset($rs_periodo)&& count($rs_periodo)>0) foreach($rs_periodo as $row){ echo "<option value='$row[Pec_Cod]' data--year='$row[Year]'>$row[Year]</option>"; } ?>                                   
                                </select>                                
                            </div>

                            <label class="col-xs-2 control-label label-xs required">Fecha:</label>  
                            <div class="col-xs-4">
                              <div class="input-group">                                          
                                  <input id="Ret_Fec" name="Ret_Fec" type="text" class="form-control input-xs readOnly datepickers ret_field"  required="" />
                                  <span class="input-group-addon input-xs" title="Fecha de la Retención"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                              </div>
                            </div>  
                        </div>
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs">Observación:</label>  
                            <div class="col-xs-10" ><textarea name="Ret_Con" class="form-control input-sm "></textarea></div>                            
                        </div>                          
                    </fieldset>
                    <fieldset class="exa-fieldset"  id="copForm">
                        <legend class="Titulos2">Sustento</legend>
                        <input name="Cop_Cod" class="hidden" value="" />
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs required">Doc.&nbsp;Sustento:</label>  
                            <div class="col-xs-9" >
                                <?php $rs_tip_compr2 = $obBD_con1->getArrayConsulta('tipo_compr.selectWhere', array('where'=>array('Tic_Est'=>'A', "Tic_Sri!=7")), $obBD_conexion); ?>
                                <select id="Tic_Cod_Sus" name="Tic_Cod_Sus" data-name="Tic_Cod" class="form-control input-xs datatrigger" tabindex="4" required=""  onchange="checkImportacion();" >                                    
                                    <?php foreach($rs_tip_compr2 as $row){                                        
                                       echo "<option value='$row[Tic_Cod]' data--tic_-sri='$row[Tic_Sri]' ".($row['Tic_Sri']*1==40?'selected="selected"':'').">$row[Tic_Sri]-$row[Tic_Des]</option>";
                                    } ?>
                                </select>
                            </div>  
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Num.&nbsp;Sustento:</label>  
                            <div class="col-xs-6">                                                                     
                                <input id="Cop_Num" name="Cop_Num" data-name="Cop_Num" type="text" class="form-control input-xs" />                         
                            </div>  
                        </div>
                        <div class="form-group">
                            <label class="col-xs-3 control-label label-xs">Fecha&nbsp;Sustento:</label>  
                            <div class="col-xs-4">                                                                     
                                <input id="Cop_Fec" name="Cop_Fec" data-name="Cop_Fec" type="text" class="form-control input-xs datepickers" />                         
                            </div>  
                        </div>
                    </fieldset>    
                    </form>
                </div>
                <div class="col-sm-7"> 
                    <div id="retencionGridParent"><table id="retencion"></table><div id="retencionPager"></div></div>
                    <div class="separator"></div>
                    <?php if(isset($atras)){ ?>
                    <button class="btn btn-sm btn-inverse" onclick="$('#divDocumento').moveComp('#divSearch').updateGridsSizes();"><i class="glyphicon glyphicon-arrow-left"></i> Atrás</button>
                    <?php } ?>
                    <button type="button" class="btn btn-sm btn-primary" onclick="$('#formDocumento').formSubmit();" ><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                </div>
                <!--INICIO DEL DIALOGO BUSCAR PROVEEDOR--> 
                <div id="provDialog" title="B&uacute;squeda de Proveedor"></div> 
                <!--INICIO DEL DIALOGO BUSCAR CODIGO--> 
                <div id="codiDialog" title="B&uacute;squeda de Códigos Retención"> 