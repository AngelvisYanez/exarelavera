<?php
/* 
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */?>
                <div class="col-sm-12">
                    <form id="searchForm" class="form-horizontal normal" action="javascript:$('#searchGridReten').Search('#searchForm','searchDocument');" >
                        <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Búsqueda</legend>                                              
                            <div class="form-group">
                                <label class="col-xs-1 control-label label-xs">Filtrar Por:</label>  
                                <div class="col-xs-4 radioset opt_search">
                                      <input id="radsc1" name="op_opciones" type="radio" value="p" checked="" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc1">&nbsp;Proveedor&nbsp;</label>
                                      <input id="radsc2" name="op_opciones" type="radio" value="c" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc2">&nbsp;C&eacute;d./RUC&nbsp;</label>
                                      <input id="radsc3" name="op_opciones" type="radio" value="d" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc3"># Retenci&oacute;n</label>
                                      <input id="radsc4" name="op_opciones" type="radio" value="b" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc4">&nbsp;# Compra&nbsp;</label>
                                </div>
                                <div class="col-xs-5">                                    
                                    <label class="col-xs-2 control-label label-xs">Periodo:</label>  
                                    <div class="col-xs-4" >
                                        <select name="Pec_Cod" class="form-control input-xs search_pec getData ins" onchange="if(this.value==='') $('#Cmb_Mes').attr('disabled','disabled'); else $('#Cmb_Mes').removeAttr('disabled'); ">
                                           <?php foreach($rs_periodo as $row){ echo "<option value='$row[Pec_Cod]' data--year='$row[Year]'>$row[Year]</option>"; } ?>
                                            <option value=""><< TODOS >></option>
                                        </select>
                                    </div> 
                                    <label class="col-xs-2 control-label label-xs">Mes:</label>  
                                    <div class="col-xs-4" >
                                        <select id="Cmb_Mes" name="Month" class="form-control input-xs search_pec">
                                           <option value=""><< TODOS >></option>
                                           <?Php  for ($i=1;$i<=12;$i++){ ?><option <?php if ($i == $mes){ echo "selected=''"; } ?> value="<?Php echo $i; ?>"><?php echo mes($i, 1); ?></option><?Php } ?>
                                        </select>
                                    </div> 
                                </div>                                 
                            </div>
                            <div class="form-group">
                                <label class="col-xs-1 control-label">B&uacute;squeda:</label>  
                                <div class="col-xs-4" >
                                    <div class="input-group">                        
                                    <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese búsqueda..." autofocus  class="form-control input-sm clearable submit"/>
                                    <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Documento"  tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                                  </div><!-- /input-group --> 
                                </div><input type="text" tabindex="-1" style="display:none;" />                    
                            </div>                        
                        </fieldset>
                    </form>
                </div>
                <div class="col-sm-12">
                    <div>
                        <table id="searchGridReten"></table><div id="searchGridRetenPager"></div>
                    </div>
                </div>
                <!-- DIALOGO DETALLE DOCUMENTO --> 
                <div id="docDetaDialog" title="Documento" style="display: none;">
                    <fieldset class="exa-fieldset" id="retViewGrid" >
                        <legend class="Titulos2">Retención:</legend>
                        <div class="form-horizontal normal" style="padding: 0 4px;">
                        <div class="form-group">
                            <label class="col-xs-1 control-label label-xs">Num.:</label>  
                            <div class="col-xs-4" ><span name="Secuencia"  class="form-control input-xs"></span></div>
                            <label class="col-xs-1 control-label label-xs">Fecha:</label>  
                            <div class="col-xs-3" ><span name="Ret_Fec"  class="form-control input-xs"></span></div>
                            <label class="col-xs-2 control-label label-xs">Autorización.:</label>  
                            <div class="col-xs-1" ><span name="Ret_Aut"  class="form-control input-xs"></span></div>
                        </div>     
                        <div class="form-group condensed">  
                            <div class="col-xs-12"><div class="pull-right"><table id="detaRete"></table></div></div>
                            <div class="col-xs-12" style="text-align: right;font-size: 8px;padding-top: 2px;"><b>CREACIÓN:</b> <span name="Ret_Sys" class="databind"></span> &nbsp;&nbsp;-&nbsp;&nbsp; <b>USUARIO:</b> <span name="Vendedor" class="databind"></span></div>
                        </div> 
                        </div>    
                    </fieldset>  
                    <fieldset class="exa-fieldset" >
                        <legend class="Titulos2">Documento:</legend>
                        <div class="form-horizontal normal" style="padding: 0 4px;">
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs">Cédula/RUC:</label>  
                            <div class="col-xs-4" ><span name="Ruc"  class="form-control input-xs"></span></div>
                            <label class="col-xs-2 control-label label-xs">Doc.Num.:</label>  
                            <div class="col-xs-4" ><span name="Cop_Num"  class="form-control input-xs"></span></div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs">Proveedor:</label>  
                            <div class="col-xs-6" ><span name="Proveedor"  class="form-control input-xs"></span></div>
                            <label class="col-xs-1 control-label label-xs">Fecha:</label>  
                            <div class="col-xs-3" ><span name="Cop_Fec"  class="form-control input-xs"></span></div>
                        </div>    
                        <div class="form-group condensed">
                            <div class="col-xs-12"><div class="pull-right"><table id="detaDocu"></table></div></div>                
                        </div> 
                        </div>    
                    </fieldset>
                </div>
                <script language="javascript" src="../VALIDACIONES/fac_val_search_reten.js"></script>