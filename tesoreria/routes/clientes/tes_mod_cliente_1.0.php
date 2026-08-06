<?php
    require_once(__DIR__.'/../../server/clientes/tes_mod_cliente_1.0.php');
?>

<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <link rel="stylesheet" type="text/css" media="screen" href="/framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <?Php require_once("../../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script type="text/javascript" src="/framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="/framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
        <!--<script type="text/javascript" src="../VALIDACIONES/tes_val_cliente.js?a=12"></script>-->
		<script type="text/javascript" src="/framework/plugins/cedulaRuc.js"></script>
        <script type="text/javascript" src="../../scripts/clientes/tes_mod_cliente_1.0.js"></script>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Modificar Clientes</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div id="lista" class="row">
                    <div class="col-md-12">
                        <form id="frm_bus" name="frm_bus" class="form-horizontal normal" action="javascript:$('#Lis_Cli').Search('#frm_bus','clientesAjax');">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">B&uacute;squeda de Clientes</legend>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label label-xs">Filtrar por:</label>
                                    <div class="col-sm-5 radioset">
                                        <input id="rad_ba1" name="op_opciones" type="radio" value="c" checked="" onclick="setfocus(this.form.search)"/><label for="rad_ba1">&nbsp;&nbsp;C&eacute;dula/R.U.C.&nbsp;&nbsp;</label>
                                        <input id="rad_ba2" name="op_opciones" type="radio" value="d" onclick="setfocus(this.form.search)"/><label for="rad_ba2">&nbsp;&nbsp;Cliente&nbsp;&nbsp;</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label label-xs">B&uacute;squeda:</label>
                                    <div class="col-sm-5">
                                        <div class="input-group">
                                            <input type="text" id="search" name="search" onkeydown="if (event.keyCode === 13)
                                                this.form.submit()" class="form-control input-xs" placeholder="Ingrese &iacute;ndice de b&uacute;squeda" autofocus="">
                                            <span class="input-group-btn">
                                                <button class="btn btn-success btn-xs" type="button" title="Buscar Cliente" onclick="this.form.submit()"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </form>
                        <div style="min-height:300px;">
                            <table id="Lis_Cli"></table>
                            <div id="Pag_Cli"></div>
                        </div>
                    </div>
                </div>
                <div id="modificar" class="row" style="display: none;">
                    <div class="col-sm-3"></div>
                    <div class="col-md-6 col-sm-8">
                        <form class="form-horizontal normal" id="formCliente" name="formCliente" action="javascript:guardarCliente();">
                            <input name="Cli_Cod" type="text" class="hidden" />
                            <input name="Prs_Cod" type="text" class="hidden" />
                            <input id="oldcedula" type="text" class="hidden" />
                            <fieldset class="exa-fieldset" >
                                <legend class="Titulos2">Datos del Cliente</legend>
                                <div class="form-group Titulos2">
                                    <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Ciudadano:</label>
                                    <div class="col-xs-5" >
                                        <div class="btn-group" data-toggle="buttons">
                                            <label id="lb_ec" class="btn btn-success btn-xs">
                                                <input id="radioec" name="tipo" value="Ec" type="radio" disabled=""><i id="spanec" class="fa fa-check"></i> Ecuatoriano
                                            </label>
                                            <label id="lb_ex" class="btn btn-default btn-xs">
                                                <input id="radioex" name="tipo" value="Ex" type="radio" disabled=""><i id="spanex" class="fa fa-check" style="display: none;"></i> Extranjero
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">C�dula/RUC:</label>  
                                    <div class="col-xs-5" >
                                        <div class="input-group input-group-xs">                                          
                                            <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs" onchange="validar(1)" required=""  readonly="" />
                                            <span class="input-group-addon validate" ><i></i></span>
											<span class="input-group-addon alert-info" ><input id="isRuc" type="checkbox" value="S" offval="N" style="vertical-align: middle;" onchange="setTipoDoc();"><b> RUC</b></span>
                                        </div>
                                    </div> 
                                    <div class="col-xs-4">
                                        <div class="checkbox check-big" style="position:absolute;">                          
                                          <label><input type="checkbox" name="Cli_Con" value="S" offval="N">Obligado Contab.</label>
                                        </div>
                                    </div>
                                </div> 
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Documento:</label>  
                                    <div class="col-xs-5" >
                                        <?php $rs_identi = $obBD_con1->getArrayConsulta(16, '', $obBD_conexion); ?>
                                        <select name="Ide_Cod" id="Ide_Cod" class="form-control input-xs readOnly" disabled="">
                                            <option value="">Seleccionar</option>
                                            <?php foreach($rs_identi as $row){ echo "<option value='$row[Ide_Cod]' data-tipo='$row[Tipo]'>$row[Ide_Des]</option>"; } ?>
                                        </select>
                                    </div>
                                </div> 
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Contribuyente:</label>  
                                    <div class="col-xs-4" >
                                        <select id="Cli_Tic" name="Cli_Tic" class="form-control input-xs" required="" onchange="if(this.value==='N'){ $('.juridico').hide();$('.natural').show(); }else{ $('.natural').hide();$('.juridico').show(); }">
                                            <option value = "N" >NATURAL</option>
                                            <option value = "J" >JURIDICO</option>
                                        </select>
                                    </div>
                                </div>                
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required"><span class='natural'>Apellidos:</span><span class='juridico' style="display: none;">Raz�n Social:</span></label>  
                                    <div class="col-xs-9" ><input name="Prs_Ape" type="text" class="form-control input-xs" required="" /></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required"><span class='natural'>Nombres:</span><span class='juridico' style="display: none;">Nomb.Comerc.:</span></label>  
                                    <div class="col-xs-9" ><input name="Prs_Nom" type="text" class="form-control input-xs" required="" /></div>
                                </div>
                                <div class="form-group natural">
                                    <label class="col-xs-3 control-label label-xs required">Genero:</label>  
                                    <div class="col-xs-4" >
                                        <select name="Prs_Sex" class="form-control input-xs">
                                            <option value = "M" >MASCULINO</option>
                                            <option value = "F" >FEMENINO</option>
                                        </select>
                                    </div>
                                </div>
                            </fieldset>
                            <fieldset class="exa-fieldset" >
                                <legend class="Titulos2">Datos de Ubicaci�n</legend>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Ciudad:</label>  
                                    <div class="col-xs-6" >
                                        <?php $rs_ciudad = $obBD_con1->getArrayConsulta(15,'',$obBD_conexion); ?>
                                        <select id="Ciu_Cod" name="Ciu_Cod" class="form-control input-xs" data-placeholder="Seleccione una ciudad" required="" >
                                            <option value=""></option>
                                            <?php  foreach($rs_ciudad as $row){ echo "<option value='$row[Ciu_Cod]' data-prov='$row[Pro_Nom]' data-pais='$row[Pas_Nom]'>$row[Ciu_Des]</option>"; } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">Direcci�n:</label>  
                                    <div class="col-xs-9" ><input name="Prs_Dir" type="text" class="form-control input-xs" required="" /></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Tel�fono(s):</label>  
                                    <div class="col-xs-9">                                    
                                        <div class="input-group input-group-xs">
                                            <span class="input-group-addon bold alert-info">#1:</span>
                                            <input name="Prs_Tel" type="text" class="form-control input-xs" pattern="\d*" onkeypress="return validar_numeric(event);" />
                                            <span class="input-group-addon bold alert-info">#2:</span>
                                            <input name="Prs_Te2" type="text" class="form-control input-xs" pattern="\d*" onkeypress="return validar_numeric(event);"/>
                                            <span class="input-group-addon bold alert-info">#3:</span>
                                            <input name="Prs_Cel" type="text" class="form-control input-xs" pattern="\d*" onkeypress="return validar_numeric(event);"/>
                                        </div>                                  
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Mail:</label>  
                                    <div class="col-xs-9" ><input name="Prs_Cor" type="email" class="form-control input-xs" multiple /></div>
                                </div>
                            </fieldset>
                            <div class="center">
                                <button type="button" onclick="$('#modificar').moveComp('#lista').updateGridsSizes();" class="btn btn-inverse fileinput-button btn-sm"><span class="glyphicon glyphicon-arrow-left"></span> Atr&aacute;s</button>
                                <button type="button" onclick="$(this.form).formSubmit();" class="btn btn-sm btn-primary no"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </BODY>
</HTML>
