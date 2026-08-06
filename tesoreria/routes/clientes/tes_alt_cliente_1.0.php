<?php
    require_once(__DIR__.'/../../server/clientes/tes_alt_cliente_1.0.php');
?>

<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
        <link rel="stylesheet" type="text/css" media="screen" href="/framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <?Php require_once(__DIR__."/../../../mascaras/model1/estilos/jqgrid5.php") ?>
        <script type="text/javascript" src="/framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="/framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
        <script type="text/javascript" src="../../VALIDACIONES/tes_val_cliente_2.0.js?a=786"></script>
        <script type="text/javascript" src="../../scripts/clientes/tes_alt_cliente_1.0.js"></script>
    </HEAD>
    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registrar Clientes</h3></div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <div class="row">
                    <div class="col-sm-3"></div>
                    <div class="col-md-6 col-sm-8">
                        <form class="form-horizontal normal" id="formCliente" name="formCliente" action="javascript:guardarCliente();">
                            <input name="Prs_Cod" type="text" class="hidden" />
                            <fieldset class="exa-fieldset" >
                                <legend class="Titulos2">Datos del Cliente</legend>
                                <div class="form-group Titulos2">
                                    <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                                </div>

                                <!--<div id="btnsCF" class="form-group " style="display:none;">
                                    col align-self-end
                                    <div class="col-sm-6"></div>
                                    <div class="col-sm-3">
                                        <button id="btnV" type="button" onclick="crearVariosIngresos()" class="btn btn-xs btn-success no" ><i class="glyphicon glyphicon-education"></i>Crear Varios Ingresos</button>
                                    </div>
                                    <div class="col-sm-3">
                                        <button id="btnC" type="button" onclick="crearConsumidorFinal()" class="btn btn-xs btn-success no" ><i class="glyphicon glyphicon-user"></i>Crear Consumidor Final</button>
                                    </div>
                                </div>-->
                                <div id="btnsCFd" class="form-group " style="display:none;">
                                    <!--col align-self-end -->
                                    <div class="col-sm-9"></div>
                                    <div class="col-sm-3">
                                        <button id="btn_Cns" type="button" onclick="crearCFConPersona()" class="btn btn-xs btn-info no" ><i class="glyphicon glyphicon-user"></i> Crear Consumidor Final</button>
                                    </div>


                                </div>
                                <div id="btnsCFdv" class="form-group " style="display:none;">
                                    <div class="col-sm-9"></div>
                                    <div class="col-sm-3">
                                        <button id="btnCVI" type="button" onclick="crearCFConPersona()" class="btn btn-xs btn-info no" ><i class="glyphicon glyphicon-tent"></i>  Crear  Varios  Ingresos</button>
                                    </div>

                                </div>

                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Ciudadano:</label>
                                    <div class="col-xs-5" >
                                        <div class="btn-group" data-toggle="buttons">
                                            <label id="lb_ec" class="btn btn-success btn-xs">
                                                <input id="radioec" name="tipo" value="Ec" type="radio" checked=""><i id="spanec" class="fa fa-check"></i> Ecuatoriano
                                            </label>
                                            <label id="lb_ex" class="btn btn-default btn-xs">
                                                <input id="radioex" name="tipo" value="Ex" type="radio"><i id="spanex" class="fa fa-check" style="display: none;"></i> Extranjero
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs required">C&eacute;dula/RUC:</label>
                                    <div class="col-xs-5" >
                                        <div class="input-group input-group-xs">
                                            <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs" onchange="validar(1)" required="" />
                                            <span class="input-group-addon validate" ><i></i></span>
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
                                <legend class="Titulos2">Datos de Ubicaci&oacute;n</legend>
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
                                    <label class="col-xs-3 control-label label-xs required">Direcci&oacute;n:</label>
                                    <div class="col-xs-9" ><input name="Prs_Dir" type="text" class="form-control input-xs" required="" /></div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-3 control-label label-xs">Tel&eacute;fono(s):</label>
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
                                <button type="button" onclick="$(this.form).formSubmit();" class="btn btn-sm btn-primary no"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
    </BODY>
</HTML>