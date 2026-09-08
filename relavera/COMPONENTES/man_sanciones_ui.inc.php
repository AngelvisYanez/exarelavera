<?php
/** TAB Sanciones + diálogos (extraído de man_adm_configuracion) */
?>
                    <!-- Tab Sanciones -->
                    <div role="tabpanel" class="tab-pane" id="tabSanciones">
                        <div class="row" style="margin-top: 5px; margin-bottom: 10px;">
                            <div class="col-xs-12">
                                <fieldset class="exa-fieldset">
                                    <legend class="Titulos2">Filtro de Búsqueda</legend>
                                    <form id="filtroSancionesForm" class="form-horizontal normal" onsubmit="event.preventDefault(); actualizarGridSanciones();">
                                        <div class="form-group" style="margin-bottom: 8px;">
                                            <label class="control-label label-xs" style="float: left; width: 100px; text-align: right; padding-right: 8px;">Filtrar Por:</label>
                                            <div class="radioset opt_search" style="float: left;">
                                                <input id="radSancion1" name="op_opciones" type="radio" value="i" checked="checked" onclick="setfocus(this.form.search)" />
                                                <label for="radSancion1">Identificación</label>
                                                <input id="radSancion2" name="op_opciones" type="radio" value="n" onclick="setfocus(this.form.search)" />
                                                <label for="radSancion2">Nombre/Apellido</label>
                                            </div>
                                            <label class="control-label label-xs" style="float: left; width: 60px; text-align: right; padding-right: 8px; margin-left: 24px; line-height: 28px;">Tipo:</label>
                                            <div style="float: left; width: 160px;">
                                                <select name="filtro_tipo" id="filtro_tipo_sanciones" class="form-control input-xs">
                                                    <option value="T">Todos</option>
                                                    <option value="VE">Vehículos</option>
                                                    <option value="CH">Choferes</option>
                                                    <option value="PL">Plantas</option>
                                                </select>
                                            </div>
                                            <div style="clear: both;"></div>
                                        </div>
                                        <div style="margin-top: 6px; margin-bottom: 4px; display: flex; align-items: center; justify-content: space-between; width: 100%;">
                                            <div style="display: flex; align-items: center; flex-grow: 1; flex-wrap: wrap;">
                                                <label class="control-label label-xs" style="width: 100px; text-align: right; padding-right: 8px; margin-bottom: 0; line-height: 32px; flex-shrink: 0;">Búsqueda:</label>
                                                <div style="width: 420px; max-width: 100%;">
                                                    <div class="input-group">
                                                        <input name="search" type="text" id="search_sanciones" maxlength="80" placeholder="Ingrese búsqueda..." class="form-control clearable" style="height: 32px; font-size: 12px;" onkeydown="if (event.keyCode === 13) { event.preventDefault(); actualizarGridSanciones(); }" />
                                                        <span class="input-group-btn">
                                                            <button type="button" onclick="actualizarGridSanciones();" class="btn btn-success" style="height: 32px; font-size: 12px;" title="Buscar">
                                                                <span class="glyphicon glyphicon-search"></span> Buscar
                                                            </button>
                                                        </span>
                                                    </div>
                                                </div>
                                                <label class="checkbox-inline" style="margin-left: 12px; margin-bottom: 0; line-height: 32px;">
                                                    <input type="checkbox" name="filtro_vigentes" id="filtro_vigentes_sanciones" value="1" />
                                                    Solo vigentes
                                                </label>
                                            </div>
                                            <?php if (!isset($esPerfilLectura) || !$esPerfilLectura) { ?>
                                            <div style="flex-shrink: 0; margin-left: 15px;">
                                                <button class="btn btn-success" type="button" onclick="abrirSancionManual();" title="Registrar sanción a vehículo, chofer o planta" style="height: 32px; font-size: 12px; font-weight: 600; padding: 0 18px;">
                                                    <i class="glyphicon glyphicon-plus"></i> Nueva sanción
                                                </button>
                                            </div>
                                            <?php } ?>
                                        </div>
                                    </form>
                                </fieldset>
                            </div>
                        </div>
                        <div class="exa-ui-grid-host">
                            <table id="gridSanciones"></table>
                            <div id="gridSancionesPager"></div>
                        </div>
                    </div>

    <!-- Diálogos de búsqueda para Sanciones -->
    <div id="vehSancionDialog" title="Buscar Vehículo"></div>
    <div id="choferSancionDialog" title="Buscar Chofer"></div>
    <div id="plantaSancionDialog" title="Buscar Planta"></div>

    <!-- Modal unificado: Sanción (Vehículo / Chofer / Planta) -->
    <div id="sancionUnificadaDialog" title="Sanción" style="display: none;">
        <ul class="nav nav-tabs" role="tablist" id="tabsSancionUnificada">
            <li class="active"><a href="#tabPaneSancionVeh" role="tab" data-toggle="tab"><i class="fa fa-car"></i> Vehículo</a></li>
            <li><a href="#tabPaneSancionCho" role="tab" data-toggle="tab"><i class="fa fa-user"></i> Chofer</a></li>
            <li><a href="#tabPaneSancionPla" role="tab" data-toggle="tab"><i class="fa fa-industry"></i> Planta</a></li>
        </ul>
        <div class="tab-content" style="margin-top: 10px;">
            <!-- Tipo sanción común a Vehículo / Chofer / Planta (un solo #Tsa_Cod) -->
            <?php $listaTiposSancion = $obBD_con1->getArrayConsultaSql("SELECT Tsa_Cod, Tsa_Des, if(Tsa_Niv='M', 'MEDIO', if(Tsa_Niv='A', 'ALTO', 'BAJO')) as Tsa_Niv FROM manifiesto_sanciones_lista WHERE Emp_Cod = $Ses_Emp_Cod AND Tsa_Est = 'A' ORDER BY Tsa_Des", $obBD_conexion); ?>
            <div class="form-horizontal normal" style="margin-bottom: 10px;">
                <div class="form-group" style="margin-bottom: 8px;">
                    <label class="col-xs-4 control-label label-xs required">Tipo Sanción:</label>
                    <div class="col-xs-8">
                        <div class="input-group input-group-xs">
                            <select id="Tsa_Cod" name="Tsa_Cod" class="form-control input-xs" required>
                                <option value="">— Seleccione —</option>
                                <?php foreach ($listaTiposSancion as $row) { ?>
                                    <option value="<?php echo $row['Tsa_Cod']; ?>" data-nivel="<?php echo $row['Tsa_Niv']; ?>"><?php echo $row['Tsa_Des']; ?></option>
                                <?php } ?>
                            </select>
                            <span class="input-group-addon" id="nivel_tipo_sancion"></span>
                            <span class="input-group-btn">
                                <?php if (!isset($esPerfilLectura) || !$esPerfilLectura) { ?>
                                <button type="button" class="btn btn-success btn-xs" title="Agregar tipo de sanci&oacute;n" onclick="abrirNuevoTipoSancion();">
                                    <i class="glyphicon glyphicon-plus"></i> Nuevo
                                </button>
                                <?php } ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div role="tabpanel" class="tab-pane fade in active" id="tabPaneSancionVeh">
                <form id="sancionVehiculoForm" class="form-horizontal normal">
                    <input type="hidden" id="sancionVeh_Msa_Cod" name="Msa_Cod">
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs required">Placa Vehículo:</label>
                        <div class="col-xs-8">
                            <div class="input-group input-group-xs">
                                <input name="Veh_Pla" id="search_veh_sancion" type="text" placeholder="Ingrese placa (ej: ABC-1234)" class="form-control input-xs" maxlength="8" onkeydown="if (event.keyCode === 13) { event.preventDefault(); buscarVehiculoPorPlacaSancion(); }" onkeyup="this.value = this.value.toUpperCase();" />
                                <span class="input-group-btn">
                                    <button type="button" onclick="buscarVehiculoPorPlacaSancion();" class="btn btn-success btn-xs" title="Buscar por placa"><span class="glyphicon glyphicon-search"></span></button>
                                </span>
                            </div>
                            <input type="hidden" id="sancionVeh_Veh_Cod" name="Veh_Cod" />
                        </div>
                        <label class="col-xs-4 control-label label-xs required">Vehículo:</label>
                        <div class="col-xs-8">
                            <span id="sancionVeh_Veh_Pla" class="form-control input-xs databind help-block" style="margin: 2px 0 0 0; font-size: 11px;"></span>
                            <span id="sancionVeh_sancionesAnio" class="help-block" style="margin: 2px 0 0 0; font-size: 11px;"></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs required">Chofer:</label>
                        <div class="col-xs-8">
                            <input type="text" id="sancionMsa_Cho" name="Msa_Cho" class="form-control input-xs" required placeholder="Chofer del Vehiculo" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs required">Fecha/Hora Inicio:</label>
                        <div class="col-xs-8">
                            <input type="datetime-local" id="sancionVeh_Msa_Fei" name="Msa_Fei" class="form-control input-xs" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs required">Fecha/Hora Fin:</label>
                        <div class="col-xs-8">
                            <input type="datetime-local" id="sancionVeh_Msa_Fef" name="Msa_Fef" class="form-control input-xs" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs">Observación:</label>
                        <div class="col-xs-8">
                            <textarea id="sancionVeh_Msa_Obs" name="Msa_Obs" class="form-control input-xs" rows="2" maxlength="500" placeholder="Observación"></textarea>
                        </div>
                    </div>
                </form>
                <div style="text-align: center; margin-top: 15px;">
                    <button class="btn btn-sm btn-primary" type="button" onclick="preGuardarSancionVehiculo();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                    <button class="btn btn-sm btn-default" type="button" onclick="cerrarModalSancionUnificada();"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
                </div>
            </div>
            <div role="tabpanel" class="tab-pane fade" id="tabPaneSancionCho">
                <form id="sancionChoferForm" class="form-horizontal normal">
                    <input type="hidden" id="sancionCho_Msa_Cod" name="Msa_Cod">
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs required">Chofer:</label>
                        <div class="col-xs-8">
                            <div class="input-group input-group-xs">
                                <input name="search_cho" id="search_cho_sancion" type="text" placeholder="Buscar chofer..." class="form-control input-xs" readonly />
                                <span class="input-group-btn">
                                    <button type="button" onclick="abrirBusquedaChoferSancion();" class="btn btn-success btn-xs" title="Buscar Chofer"><span class="glyphicon glyphicon-search"></span></button>
                                </span>
                            </div>
                            <input type="hidden" id="sancionCho_Cho_Cod" name="Cho_Cod" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs">Cédula:</label>
                        <div class="col-xs-8">
                            <span id="sancionCho_Prs_Ced" class="form-control input-xs databind"></span>
                            <span id="sancionCho_sancionesAnio" class="help-block" style="margin: 2px 0 0 0; font-size: 11px;"></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs required">Fecha/Hora Inicio:</label>
                        <div class="col-xs-8">
                            <input type="datetime-local" id="sancionCho_Msa_Fei" name="Msa_Fei" class="form-control input-xs" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs required">Fecha/Hora Fin:</label>
                        <div class="col-xs-8">
                            <input type="datetime-local" id="sancionCho_Msa_Fef" name="Msa_Fef" class="form-control input-xs" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs">Observación:</label>
                        <div class="col-xs-8">
                            <textarea id="sancionCho_Msa_Obs" name="Msa_Obs" class="form-control input-xs" rows="2" maxlength="500" placeholder="Observación"></textarea>
                        </div>
                    </div>
                </form>
                <div style="text-align: center; margin-top: 15px;">
                    <button class="btn btn-sm btn-primary" type="button" onclick="guardarSancionChofer();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                    <button class="btn btn-sm btn-default" type="button" onclick="cerrarModalSancionUnificada();"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
                </div>
            </div>
            <div role="tabpanel" class="tab-pane fade" id="tabPaneSancionPla">
                <form id="sancionPlantaForm" class="form-horizontal normal">
                    <input type="hidden" id="sancionPla_Msa_Cod" name="Msa_Cod">
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs required">Planta:</label>
                        <div class="col-xs-8">
                            <div class="input-group input-group-xs">
                                <input name="search_pla" id="search_pla_sancion" type="text" placeholder="Buscar planta..." class="form-control input-xs" readonly />
                                <span class="input-group-btn">
                                    <button type="button" onclick="abrirBusquedaPlantaSancion();" class="btn btn-success btn-xs" title="Buscar Planta"><span class="glyphicon glyphicon-search"></span></button>
                                </span>
                            </div>
                            <input type="hidden" id="sancionPla_Pla_Cod" name="Pla_Cod" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs">Cédula cliente:</label>
                        <div class="col-xs-8">
                            <span id="sancionPla_Prs_Ced" class="form-control input-xs databind"></span>
                            <span id="sancionPla_sancionesAnio" class="help-block" style="margin: 2px 0 0 0; font-size: 11px;"></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs required">Fecha/Hora Inicio:</label>
                        <div class="col-xs-8">
                            <input type="datetime-local" id="sancionPla_Msa_Fei" name="Msa_Fei" class="form-control input-xs" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs required">Fecha/Hora Fin:</label>
                        <div class="col-xs-8">
                            <input type="datetime-local" id="sancionPla_Msa_Fef" name="Msa_Fef" class="form-control input-xs" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs">Observación:</label>
                        <div class="col-xs-8">
                            <textarea id="sancionPla_Msa_Obs" name="Msa_Obs" class="form-control input-xs" rows="2" maxlength="500" placeholder="Observación"></textarea>
                        </div>
                    </div>
                </form>
                <div style="text-align: center; margin-top: 15px;">
                    <button class="btn btn-sm btn-primary" type="button" onclick="guardarSancionPlanta();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                    <button class="btn btn-sm btn-default" type="button" onclick="cerrarModalSancionUnificada();"><i class="glyphicon glyphicon-remove"></i> Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Nuevo Tipo de Sanción -->
    <div id="nuevoTipoSancionDialog" title="Nuevo Tipo de Sanción" style="display: none;">
        <form id="nuevoTipoSancionForm" class="form-horizontal normal">
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Descripción:</label>
                <div class="col-xs-8">
                    <input type="text" id="nuevoTsa_Des" name="Tsa_Des" class="form-control input-xs" required maxlength="100" placeholder="Descripción del tipo de sanción" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Nivel Riesgo:</label>
                <div class="col-xs-8">
                    <select id="nuevoTsa_Niv" name="Tsa_Niv" class="form-control input-xs" required>
                        <option value="">— Seleccione —</option>
                        <option value="A">ALTO</option>
                        <option value="B">BAJO</option>
                        <option value="M">MEDIO</option>
                    </select>
                </div>
            </div>
        </form>
        <div style="text-align: center; margin-top: 15px;">
            <button class="btn btn-sm btn-primary" type="button" onclick="guardarNuevoTipoSancion();">
                <i class="glyphicon glyphicon-floppy-disk"></i> Guardar
            </button>
            <button class="btn btn-sm btn-default" type="button" onclick="$('#nuevoTipoSancionDialog').dialog('close');">
                <i class="glyphicon glyphicon-remove"></i> Cancelar
            </button>
        </div>
    </div>

