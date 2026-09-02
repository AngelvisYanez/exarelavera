<?php

/* 
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */
if (!isset($hoy) || $hoy === '') {
    $hoy = date('Y-m-d');
}
?>
<style>
    .footrow td[aria-describedby="documento_Cop_Imp"],
    .footrow td[aria-describedby="documento_Cop_Pru"] {
        padding: 0 !important;
    }

    .footerFact {
        text-align: right;
        width: 100%;
    }

    .footerFact input[type=text],
    .footerFact label,
    .footerFact textarea,
    .footerFact select {
        height: 19px;
        width: 100% !important;
        display: block;
        margin-bottom: 0px !important;
        margin-top: 0px !important;
        text-align: right;
    }

    .footerFact input[type=text] {
        padding: 0;
    }

    .footerFact textarea {
        text-align: left;
        height: 75px !important;
    }

    .footerFact select {
        padding-top: 2px !important;
        padding-bottom: 2px !important;
        display: inline;
    }

    .footerFact label {
        height: 19px;
        line-height: 18px;
        padding-right: 5px;
    }

    .footerFact label.total,
    .footerFact input.total {
        background-color: #254463;
        color: white;
        font-size: 14px;
        border: none;
    }

    #Ret_Asu {
        vertical-align: middle;
        margin-top: -2px;
        padding: 5px;
        -ms-transform: scale(1.4);
        -moz-transform: scale(1.4);
        -webkit-transform: scale(1.4);
        -o-transform: scale(1.4);
    }

    #resultContent .resp {
        font-weight: 700;
        font-size: 30px;
        color: #3f3fc1;
        padding: 0;
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        height: 32px;
    }

    #resultContent .resp span:first-child {
        color: darkgoldenrod;
        width: 100px;
        display: inline-block;
        margin-left: 42px;
    }

    .ret .input-group-btn button {
        padding: 1px 2px !important;
    }

    .ret {
        padding: 0 !important;
    }
</style>
<form id="formDocumento" class="form-horizontal normal formDatos" action="javascript:validaDocument();">
    <div class="row">
        <div class="col-xs-5">
            <fieldset class="exa-fieldset" id="provFormTemp">
                <legend class="Titulos2">Datos del Proveedor</legend>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Cédula/RUC:</label>
                    <div class="col-xs-6">
                        <input name="Prs_Cod" type="text" style="display:none;" />
                        <input name="Prv_Cod" type="text" style="display:none;" />
                        <input name="op_opciones" type="text" value="c" style="display: none;">
                        <div class="input-group input-group-xs">
                            <input name="Prs_Ced" onkeydown="if (event.keyCode === 13) $.SearchOrDialog('#provDialog',selectProvee);" type="text" placeholder="Ingrese Proveedor..." class="form-control input-xs clearable dialogSearch" tabindex="1" />
                            <span class="input-group-btn">
                                <button id="Prv_Btn" type="button" onclick="$('#provDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Proveedor" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                <button type="button" onclick="$('#provCreateForm').setData({Prv_Esp:'N',Prv_Con:'N'}).find('.validate').find('i').removeAttr('class'); $('#provCreateDialog').dialog('open');" class="btn btn-success btn-xs" title="Registrar Proveedor" tabindex="2"><span class="glyphicon glyphicon-plus"></span></button>
                            </span>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <span class="radioset">
                            <input id="op_ide1" name="Cop_Ide" type="radio" value="1" disabled style="cursor:pointer" onchange=""><label title="Documento del proveedor tipo R.U.C" for="op_ide1">&nbsp;Ruc&nbsp; </label>
                            <input id="op_ide2" name="Cop_Ide" type="radio" value="2" disabled style="cursor:pointer" onchange=""><label title="Documento del proveedor tipo CEDULA" for="op_ide2">&nbsp;Ced&nbsp;</label>
                            <input id="op_ide3" name="Cop_Ide" type="radio" value="3" disabled style="cursor:pointer" onchange=""><label title="Documento del proveedor tipo PASAPORTE" for="op_ide3">&nbsp;Pas&nbsp;</label>
                        </span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs required">Proveedor:</label>
                    <?php if (!isset($insert)) { ?>
                        <div class="col-xs-6"><span name="proveedor" class="form-control input-xs databind datatitle"></span></div>
                    <?php } else { ?>
                        <div class="col-xs-6">
                            <div class="input-group input-group-xs">
                                <span name="proveedor" class="form-control input-xs databind datatitle"></span>
                                <span class="input-group-btn"> <button type="button" id='cargarElectronico' onclick="$('#formElectronico').setData({}); $('#loadXml').dialog('open');   " class="btn btn-success btn-xs" title="Cargar Documento Electrónico" tabindex="-1"><span class="fa fa-globe"></span></button> </span>
                            </div>
                        </div>
                    <?php } ?>
                    <label class="col-xs-2 control-label label-xs">Oblig.Contab:&nbsp;<i id="Prv_Con" class="blue glyphicon glyphicon-remove" style="font-size: 12px;"></i></label>
                    <label class=" control-label label-xs">Contr.Especial:&nbsp;<i id="Prv_Esp" class="blue glyphicon glyphicon-remove" style="font-size: 12px;"></i></label>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Dirección:</label>
                    <div class="col-xs-10">
                        <div class="input-group input-group-xs">
                            <input name="Prs_Dir" type="text" class="form-control span datatitle" readonly="" tabindex="-1">
                            <span class="input-group-addon bold">e-mail:</span>
                            <input name="Prs_Cor" type="text" class="form-control span datatitle" readonly="" tabindex="-1" />
                        </div>
                    </div>
                <?php if ($configs['Cof_Sld'] == 'S') { ?>
                        <label class="col-xs-2 control-label label-xs" style="text-decoration: underline; margin-left: -10px;">Saldo de CCxPP:</label>
                        <div class="col-xs-4" style="margin-top: 10px;margin-left: 10px;">
                            <input id="Prv_Sal" name="Prv_Sal" type="text" class="form-control input-xs databind" style="text-align: right;" readonly />
                        </div>
                    </div>
                <?php } ?>
            </fieldset>


            <?php $cen_cons = $obBD_con1->getArrayConsulta('consumo.selectWhere', array('clean' => true, 'where' => array('Emp_Cod' => $Ses_Emp_Cod, 'Con_Est' => 'A')), $obBD_conexion); ?>
            <?php $bodegas = $obBD_con1->getArrayConsulta('bodega.1', array('Suc_Cod' => $Ses_Suc_Cod, 'Usu_Cod' => $Ses_Usu_Cod), $obBD_conexion); ?>
            <?php
            $verNeg = (isset($rs_infoEmpresa["Cof_NegCam"]) && $rs_infoEmpresa["Cof_NegCam"] == 'S');
            $verCon = count($cen_cons) > 0;
            $verBod = count($bodegas) > 0;
            $verPre = (isset($configs['Cof_Mpe']) && $configs['Cof_Mpe'] == 'S')
                || (isset($rs_infoEmpresa['Cof_Mpe']) && $rs_infoEmpresa['Cof_Mpe'] == 'S');
            ?>

            <!-- Asignación compacta: Negociación / Consumo / Bodega / Presupuesto -->
            <style>
                /* Compacta el espacio entre este bloque y el fieldset anterior. */
                .normal.form-horizontal #provFormTemp { margin-bottom: 3px; }
                #asigFieldset { margin-top: 0; margin-bottom: 4px; padding: 0 4px 2px; background: linear-gradient(180deg, #fbfdff 0, #f4f8fb 100%); border-color: #dde7ee !important; border-radius: 4px; }
                .exa-asig { padding: 2px 2px; }
                .exa-asig > .tt { display: inline-block; margin-right: 8px; font-size: 10px; font-weight: 700; letter-spacing: .5px; color: #9db1bf; text-transform: uppercase; vertical-align: middle; }
                .exa-asig-chip { display: inline-block; max-width: 100%; margin: 1px 5px 1px 0; padding: 2px 10px 2px 7px; border: 1px solid #dbe3ea; border-radius: 12px; background: #fff; font-size: 11px; line-height: 17px; white-space: nowrap; cursor: pointer; box-shadow: 0 1px 1px rgba(19, 58, 86, .06); transition: box-shadow .15s, border-color .15s, background-color .15s; }
                .exa-asig-chip:hover { box-shadow: 0 2px 5px rgba(19, 58, 86, .16); }
                .exa-asig-chip .ic { display: inline-block; width: 16px; height: 16px; margin-right: 5px; border-radius: 50%; color: #fff; font-size: 9px; line-height: 16px; text-align: center; vertical-align: text-bottom; }
                .exa-asig-chip .k { color: #90a4b3; }
                .exa-asig-chip .v { display: inline-block; max-width: 145px; overflow: hidden; text-overflow: ellipsis; vertical-align: bottom; font-weight: 600; }
                .exa-asig-chip .caret { margin-left: 4px; opacity: .5; transition: transform .15s; }
                .exa-asig-chip.active .caret { transform: rotate(180deg); }
                .exa-asig-chip.empty { border-style: dashed; background: #fbfcfd; }
                .exa-asig-chip.empty .ic { background: #c3ced7 !important; }
                .exa-asig-chip.empty .v { color: #b3bfc9; font-weight: 400; }
                /* Marcado: el grupo ya tiene un dato asignado; el visto reemplaza al caret. */
                .exa-asig-chip.sel { box-shadow: 0 1px 3px rgba(19, 58, 86, .14); }
                .exa-asig-chip.sel .caret { display: none; }
                .exa-asig-chip.sel:after { content: "\e013"; margin-left: 5px; font-family: 'Glyphicons Halflings'; font-size: 9px; }

                /* Acentos por tipo */
                .exa-asig-chip[data-asig=neg] .ic { background: #3f7fb5; }
                .exa-asig-chip[data-asig=neg] .v { color: #2f6a9b; }
                .exa-asig-chip[data-asig=neg]:hover, .exa-asig-chip[data-asig=neg].active, .exa-asig-chip[data-asig=neg].sel { border-color: #3f7fb5; background: #eef5fb; }
                .exa-asig-chip[data-asig=neg].sel:after { color: #2f6a9b; }
                .exa-asig-chip[data-asig=con] .ic { background: #2e9e8f; }
                .exa-asig-chip[data-asig=con] .v { color: #237f73; }
                .exa-asig-chip[data-asig=con]:hover, .exa-asig-chip[data-asig=con].active, .exa-asig-chip[data-asig=con].sel { border-color: #2e9e8f; background: #ecf8f6; }
                .exa-asig-chip[data-asig=con].sel:after { color: #237f73; }
                .exa-asig-chip[data-asig=bod] .ic { background: #d38b2b; }
                .exa-asig-chip[data-asig=bod] .v { color: #a96a13; }
                .exa-asig-chip[data-asig=bod]:hover, .exa-asig-chip[data-asig=bod].active, .exa-asig-chip[data-asig=bod].sel { border-color: #d38b2b; background: #fdf5e9; }
                .exa-asig-chip[data-asig=bod].sel:after { color: #a96a13; }
                .exa-asig-chip[data-asig=pre] .ic { background: #7a5ea8; }
                .exa-asig-chip[data-asig=pre] .v { color: #5f4588; }
                .exa-asig-chip[data-asig=pre]:hover, .exa-asig-chip[data-asig=pre].active, .exa-asig-chip[data-asig=pre].sel { border-color: #7a5ea8; background: #f4effa; }
                .exa-asig-chip[data-asig=pre].sel:after { color: #5f4588; }

                /* El contenido se muestra flotando sobre la página: la fila nunca cambia de alto. */
                .exa-asig-pop { display: none; position: absolute; z-index: 1050; min-width: 255px; max-width: 340px; padding: 8px 9px 9px; background: #fff; border: 1px solid #d7e2ea; border-top: 3px solid #3f7fb5; border-radius: 4px; box-shadow: 0 6px 18px rgba(19, 58, 86, .18); }
                .exa-asig-pop.open { display: block; animation: asigIn .13s ease-out; }
                .exa-asig-pop[data-asig=con] { border-top-color: #2e9e8f; }
                .exa-asig-pop[data-asig=bod] { border-top-color: #d38b2b; }
                .exa-asig-pop[data-asig=pre] { border-top-color: #7a5ea8; min-width: 300px; }
                #ppaRowPop { position: fixed; z-index: 1200; min-width: 300px; max-width: 360px; }
                .exa-asig-pop .tit { display: block; margin-bottom: 4px; font-size: 10px; font-weight: 600; letter-spacing: .5px; color: #8ba0ae; text-transform: uppercase; }
                .exa-asig-pop[data-asig=neg] .tit { color: #3f7fb5; }
                .exa-asig-pop[data-asig=con] .tit { color: #2e9e8f; }
                .exa-asig-pop[data-asig=bod] .tit { color: #c07d20; }
                .exa-asig-pop[data-asig=pre] .tit { color: #7a5ea8; }
                .exa-asig-pop .ruta { display: block; margin-top: 3px; font-size: 10px; color: #8ba0ae; white-space: normal; line-height: 1.25; }
                @keyframes asigIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: none; } }

                /* Ruta jerárquica del rubro: mismo árbol en la celda del grid y en el popover. */
                #ppaDialog td.ppa-arbol { white-space: normal !important; padding-top: 2px !important; padding-bottom: 2px !important; }
                .exa-arbol { display: block; }
                .exa-arbol .n { display: block; color: #94a5b2; font-size: 10px; line-height: 1.35; }
                .exa-arbol .n.pad { color: #6b7d8b; font-weight: 600; }
                .exa-arbol .n .cn { color: #c8d2da; }
                .exa-sinpadre { color: #b3bfc9; font-size: 10px; font-style: italic; }

                /* Filtros compactos del modal de presupuesto */
                #ppaDialog .ppa-filtros { display: table; width: 100%; table-layout: fixed; border-spacing: 4px 0; margin: 0 -4px 3px; }
                #ppaDialog .ppa-fg { display: table-cell; vertical-align: middle; }
                #ppaDialog .ppa-fg .input-group,
                #ppaDialog .ppa-texto .input-group { width: 100%; }
                #ppaDialog .ppa-fg .input-group-addon { padding: 2px 6px; color: #5f4588; background: #f4effa; border-color: #e4dcf0; font-size: 10px; font-weight: 700; letter-spacing: .2px; white-space: nowrap; }
                #ppaDialog .ppa-fg .form-control,
                #ppaDialog .ppa-fg .btn,
                #ppaDialog .ppa-texto .form-control,
                #ppaDialog .ppa-texto .btn { height: 24px; }
                #ppaDialog .ppa-fg select.form-control { text-overflow: ellipsis; }
                .ppa-tip { max-width: 440px; padding: 5px 8px; font-size: 11px; line-height: 1.35; }
                #ppaDialog .ppa-hint { display: block; margin: 0; min-height: 14px; color: #8b79a8; font-size: 10px; line-height: 14px; }
                #ppaDialog .ppa-hint b { color: #5f4588; }
                #ppaDialog .ppa-origen { margin: 0 0 4px; }
                #ppaDialog .ppa-origen label { margin: 0 12px 0 0; font-size: 11px; font-weight: 600; color: #5f4588; cursor: pointer; }
                #ppaDialog .ppa-origen input { margin: 0 4px 0 0; vertical-align: -1px; }
                #ppaDialog .ppa-proy-only.is-off { opacity: .45; pointer-events: none; }
                #ppaDialog #frm_ppa fieldset.exa-fieldset { margin: 0 0 4px; padding: 4px 6px 3px; }
                #ppaDialog #frm_ppa fieldset.exa-fieldset > legend { display: none; }
            </style>
            <fieldset class="exa-fieldset" id="asigFieldset" style="position:relative;<?php if (!$verNeg && !$verCon && !$verBod && !$verPre) echo 'display:none;'; ?>">
                <div class="exa-asig" id="asigChips">
                    <span class="tt">Asignar</span>
                    <?php if ($verNeg) { ?>
                        <span class="exa-asig-chip empty" data-asig="neg" title="Negociación"><i class="ic glyphicon glyphicon-briefcase"></i><span class="k">Negociación:</span> <span class="v">—</span><span class="caret"></span></span>
                    <?php } ?>
                    <?php if ($verCon) { ?>
                        <span class="exa-asig-chip empty" data-asig="con" title="Centro de consumo"><i class="ic glyphicon glyphicon-tags"></i><span class="k">Consumo:</span> <span class="v">—</span><span class="caret"></span></span>
                    <?php } ?>
                    <?php if ($verBod) { ?>
                        <span class="exa-asig-chip empty" data-asig="bod" title="Bodega"><i class="ic glyphicon glyphicon-home"></i><span class="k">Bodega:</span> <span class="v">—</span><span class="caret"></span></span>
                    <?php } ?>
                    <?php if ($verPre) { ?>
                        <span class="exa-asig-chip empty" data-asig="pre" title="Rubro de presupuesto"><i class="ic glyphicon glyphicon-stats"></i><span class="k">Presupuesto:</span> <span class="v">—</span><span class="caret"></span></span>
                    <?php } ?>
                </div>
                <?php if ($verNeg) { ?>
                    <div class="exa-asig-pop" data-asig="neg" id="formNeg">
                        <span class="tit">Negociación</span>
                        <div class="input-group input-group-xs">
                            <input type="text" name="Num_Neg" id="Num_Neg" placeholder="Ingrese cod.Negociación..." class="form-control input-xs " readonly />
                            <input type="text" name="Cod_Neg" id="Cod_Neg" style="display:none;" />
                            <input type="text" name="Cod_Nd" id="Cod_Nd" style="display:none;" />
                            <span class="input-group-btn">
                                <button id="Prv_Btn_" type="button" onclick="$('#negDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Negociación" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                <button type="button" onclick="limpiarCamposNego()" class="btn btn-success btn-xs" title="Quitar Negociación"><span class="glyphicon glyphicon-remove"></span></button>
                            </span>
                        </div>
                    </div>
                <?php } ?>
                <?php if ($verCon) { ?>
                    <div class="exa-asig-pop" data-asig="con">
                        <span class="tit">Centro de consumo</span>
                        <select name="Con_Cod" class="form-control input-xs">
                            <option value="" selected="">NINGUNO</option>
                            <?php foreach ($cen_cons as $row) {
                                echo "<option value='$row[Con_Cod]'>$row[Con_Des]</option>";
                            } ?>
                        </select>
                    </div>
                <?php } else { ?>
                    <select name="Con_Cod" class="form-control input-xs" style="display:none;">
                        <option value="" selected="">NINGUNO</option>
                    </select>
                <?php } ?>
                <?php if ($verBod) { ?>
                    <div class="exa-asig-pop" data-asig="bod">
                        <span class="tit">Bodega</span>
                        <select id="Bod_Cod" name="Bod_Cod" class="form-control input-xs">
                            <?php foreach ($bodegas as $row) {
                                echo "<option value='$row[Bod_Cod]'>$row[Bod_Nom]</option>";
                            } ?>
                        </select>
                    </div>
                <?php } else { ?>
                    <select id="Bod_Cod" name="Bod_Cod" class="form-control input-xs" style="display:none;"></select>
                <?php } ?>
                <?php if ($verPre) { ?>
                    <div class="exa-asig-pop" data-asig="pre" id="formPre">
                        <span class="tit">Rubro presupuestario</span>
                        <div class="input-group input-group-xs">
                            <input type="text" name="Ppa_Label" id="Ppa_Label" placeholder="Seleccione rubro..." class="form-control input-xs" readonly />
                            <input type="hidden" name="Ppa_Cod" id="Ppa_Cod" value="" />
                            <input type="hidden" name="Pdp_Cod" id="Pdp_Cod" value="" />
                            <input type="hidden" name="Ppa_Cla" id="Ppa_Cla" value="" />
                            <input type="hidden" name="Ppa_Des" id="Ppa_Des" value="" />
                            <input type="hidden" name="Ppa_Ruta" id="Ppa_Ruta" value="" />
                            <span class="input-group-btn">
                                <button type="button" onclick="abrirBusquedaPresupuesto()" class="btn btn-success btn-xs" title="Buscar rubro de presupuesto" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                                <button type="button" onclick="limpiarCamposPresupuesto()" class="btn btn-danger btn-xs" title="Quitar rubro"><span class="glyphicon glyphicon-remove"></span></button>
                            </span>
                        </div>
                        <small class="ruta" id="Ppa_Ruta_Txt"></small>
                    </div>
                    <!-- Mismo cuadro para el rubro de cada fila del grid de detalle. -->
                    <div class="exa-asig-pop" data-asig="pre" id="ppaRowPop">
                        <span class="tit">Rubro presupuestario</span>
                        <div class="input-group input-group-xs">
                            <input type="text" id="ppaRow_Label" placeholder="Seleccione rubro..." class="form-control input-xs" readonly />
                            <span class="input-group-btn">
                                <button type="button" onclick="abrirBusquedaPresupuesto(true)" class="btn btn-success btn-xs" title="Buscar / cambiar rubro"><span class="glyphicon glyphicon-search"></span></button>
                                <button type="button" onclick="clearPpaRowFromPop()" class="btn btn-danger btn-xs" title="Quitar rubro"><span class="glyphicon glyphicon-remove"></span></button>
                            </span>
                        </div>
                        <small class="ruta" id="ppaRow_Ruta_Txt"></small>
                    </div>
                <?php } ?>
            </fieldset>
            <?php if ($verPre) { ?>
            <?php
            $ppa_proyectos = array();
            $ppa_sol_rubros_ok = false;
            if (isset($obBD_conexion) && !empty($obBD_conexion->conexion) && !empty($Ses_Emp_Cod)) {
                $empPpa = (int)$Ses_Emp_Cod;
                $mysqliPpa = $obBD_conexion->conexion;
                if (!function_exists('fac_ppa_tabla_existe')) {
                    function fac_ppa_tabla_existe($mysqli, $tabla)
                    {
                        if (!$mysqli || $tabla === '') {
                            return false;
                        }
                        $t = $mysqli->real_escape_string($tabla);
                        $res = @$mysqli->query("SHOW TABLES LIKE '$t'");
                        return $res && $res->num_rows > 0;
                    }
                }
                $ppa_sol_rubros_ok = fac_ppa_tabla_existe($mysqliPpa, 'adq_solicitudes_rubros')
                    && (fac_ppa_tabla_existe($mysqliPpa, 'adq_solicitudes') || fac_ppa_tabla_existe($mysqliPpa, 'adq_solicitud'));
                $qPpaProy = @$mysqliPpa->query(
                    "SELECT Pro_Cod, Pro_Ide, Pro_Nom FROM pre_proyectos WHERE Emp_Cod = $empPpa AND Pro_Est = 'A' ORDER BY Pro_Ide, Pro_Nom"
                );
                if ($qPpaProy) {
                    while ($rowPpaProy = $qPpaProy->fetch_assoc()) {
                        $ppa_proyectos[] = $rowPpaProy;
                    }
                }
            }
            ?>
            <?php /* Sin <form> interno: este componente se renderiza dentro de #formDocumento y el navegador descarta formularios anidados. */ ?>
            <div id="ppaDialog" title="B&uacute;squeda de Rubros de Presupuesto" style="display:none;" data-sol-rubros="<?php echo $ppa_sol_rubros_ok ? '1' : '0'; ?>">
                <div id="frm_ppa" class="form-horizontal normal">
                    <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Filtros</legend>
                        <div class="ppa-origen">
                            <label title="Filtrar por proyecto<?php echo $ppa_sol_rubros_ok ? ' y/o No. de solicitud' : ''; ?>">
                                <input type="radio" name="ppa_Origen" id="ppa_Origen_proyecto" value="proyecto" checked /> Presupuesto por Proyecto
                            </label>
                            <label title="Solo rubros del catálogo que no pertenecen a ningún proyecto">
                                <input type="radio" name="ppa_Origen" id="ppa_Origen_general" value="general" /> Presupuesto General
                            </label>
                        </div>
                        <div class="ppa-filtros ppa-proy-only">
                            <div class="ppa-fg" style="width:<?php echo $ppa_sol_rubros_ok ? '40%' : '100%'; ?>;">
                                <div class="input-group input-group-xs">
                                    <span class="input-group-addon" title="Por Proyecto">Proyecto</span>
                                    <select id="ppa_Pro_Cod" class="form-control input-xs" title="Proyecto">
                                        <option value="">Todos...</option>
                                        <?php foreach ($ppa_proyectos as $proyPpa) {
                                            $lblPpa = trim($proyPpa['Pro_Ide'] . ' - ' . $proyPpa['Pro_Nom']);
                                            $lblPpaEsc = htmlspecialchars($lblPpa, ENT_QUOTES, 'UTF-8');
                                            echo '<option value="' . (int)$proyPpa['Pro_Cod'] . '" title="' . $lblPpaEsc . '">' . $lblPpaEsc . '</option>';
                                        } ?>
                                    </select>
                                </div>
                            </div>
                            <?php if ($ppa_sol_rubros_ok) { ?>
                            <div class="ppa-fg ppa-sol-filtro" style="width:32%;">
                                <div class="input-group input-group-xs">
                                    <span class="input-group-addon" title="No. Solicitud">No. Sol.</span>
                                    <select id="ppa_Sol_Cod" class="form-control input-xs" title="No Solicitud" disabled>
                                        <option value="">Seleccione...</option>
                                    </select>
                                </div>
                            </div>
                            <div class="ppa-fg ppa-sol-filtro" style="width:28%;">
                                <div class="input-group input-group-xs">
                                    <input id="ppa_Sol_Num" type="text" maxlength="40" placeholder="N° solicitud" class="form-control input-xs" title="Buscar por número de solicitud" onkeydown="if((event.keyCode||event.which)===13){event.preventDefault();window.buscarSolicitudPresupuesto();return false;}" />
                                    <span class="input-group-btn">
                                        <button type="button" id="ppa_Sol_Num_Btn" onclick="window.buscarSolicitudPresupuesto()" class="btn btn-success btn-xs" title="Buscar por N° solicitud">
                                            <span class="glyphicon glyphicon-search"></span>
                                        </button>
                                    </span>
                                </div>
                            </div>
                            <?php } else { ?>
                            <input type="hidden" id="ppa_Sol_Cod" value="" />
                            <input type="hidden" id="ppa_Sol_Num" value="" />
                            <?php } ?>
                        </div>
                        <div class="ppa-texto">
                            <div class="input-group input-group-xs">
                                <input id="ppa_search" type="text" maxlength="80" placeholder="Filtrar rubros por código, nombre o ruta..." class="form-control input-xs clearable" onkeydown="if((event.keyCode||event.which)===13){event.preventDefault();window.buscarPresupuesto();return false;}" />
                                <span class="input-group-btn">
                                    <button type="button" onclick="window.buscarPresupuesto()" class="btn btn-success btn-xs" title="Filtrar rubros">
                                        <span class="glyphicon glyphicon-filter"></span>
                                    </button>
                                </span>
                            </div>
                        </div>
                        <small class="ppa-hint" id="ppa_hint"><?php echo $ppa_sol_rubros_ok
                            ? 'Presupuesto por Proyecto: filtre y pulse buscar para listar rubros.'
                            : 'Presupuesto por Proyecto: filtre por proyecto y pulse buscar (sin solicitudes).'; ?></small>
                    </fieldset>
                </div>
                <table id="containerPpa"></table>
            </div>
            <script>window.ppaSolRubrosOk = <?php echo $ppa_sol_rubros_ok ? 'true' : 'false'; ?>;</script>
            <?php } ?>
            <script>
                (function () {
                    /* Los valores se asignan por .val() desde otros scripts, que no dispara eventos: se sincroniza por sondeo. */
                    var last = {};
                    function valorDe(tipo) {
                        if (tipo === 'neg') return ($('#Num_Neg').val() || '').trim();
                        if (tipo === 'pre') return ($('#Ppa_Label').val() || '').trim();
                        var $s = (tipo === 'con' ? $('select[name=Con_Cod]') : $('#Bod_Cod'));
                        if (!$s.length) return '';
                        var v = $s.val();
                        return (v === '' || v == null) ? '' : $.trim($s.find('option:selected').text());
                    }
                    function pintarChips() {
                        $('#asigChips .exa-asig-chip').each(function () {
                            var $c = $(this), tipo = $c.data('asig'), v = valorDe(tipo);
                            if (last[tipo] === v) return;
                            last[tipo] = v;
                            $c.toggleClass('empty', v === '').toggleClass('sel', v !== '')
                                .find('.v').text(v === '' ? '—' : v).end()
                                .attr('title', $c.find('.k').text() + ' ' + (v === '' ? 'sin asignar' : v));
                        });
                        var $rt = $('#Ppa_Ruta_Txt');
                        if ($rt.length) {
                            var ruta = $('#Ppa_Ruta').val() || '';
                            if ($rt.data('ruta') !== ruta) $rt.data('ruta', ruta).html(ruta === '' ? '' : rutaArbol(ruta));
                        }
                    }
                    function cerrarPop() {
                        $('#asigChips .exa-asig-chip').removeClass('active');
                        $('#asigFieldset .exa-asig-pop').not('#ppaRowPop').removeClass('open');
                        window.cerrarPpaRowPop();
                    }
                    window.cerrarPpaRowPop = function () {
                        $('#ppaRowPop').removeClass('open');
                    };
                    window.clearPpaRowFromPop = function () {
                        var id = window.ppaRowIndex;
                        if (typeof clearPpaRow === 'function' && id) clearPpaRow(id);
                        window.cerrarPpaRowPop();
                        window.ppaRowIndex = null;
                    };
                    /** Muestra el mismo cuadro del chip, anclado a la celda de la fila. */
                    window.mostrarPpaRowPop = function (data, rowId) {
                        var $pop = $('#ppaRowPop');
                        if (!$pop.length) return;
                        data = data || {};
                        var label = data['Ppa_Label'] || ((data['Ppa_Cla'] || '') + ' - ' + (data['Pdp_Rubro'] || data['Ppa_Des'] || ''));
                        $('#ppaRow_Label').val($.trim(label));
                        $('#ppaRow_Ruta_Txt').html(rutaArbol(data['Ppa_Ruta'] || ''));
                        window.ppaRowIndex = rowId;
                        $('#asigChips .exa-asig-chip').removeClass('active');
                        $('#asigFieldset .exa-asig-pop').not('#ppaRowPop').removeClass('open');
                        var $td = $('#documento tr#' + rowId + ' td[aria-describedby="documento_selectPpa"]');
                        var off = ($td.length ? $td.offset() : null) || { top: 120, left: 200 };
                        var tw = $td.length ? $td.outerWidth() : 30;
                        var th = $td.length ? $td.outerHeight() : 22;
                        var left = off.left + tw - Math.max($pop.outerWidth(), 300);
                        if (left < 8) left = 8;
                        $pop.css({ top: off.top + th + 2, left: left }).addClass('open');
                    };
                    window.limpiarCamposPresupuesto = function () {
                        var oldPpa = String($('#Ppa_Cod').val() || '');
                        var oldPdp = String($('#Pdp_Cod').val() || '');
                        $('#Ppa_Cod,#Pdp_Cod,#Ppa_Cla,#Ppa_Des,#Ppa_Label,#Ppa_Ruta').val('');
                        /* Al quitar el rubro general también hay que limpiar las filas del detalle
                           (en edición vienen enriquecidas con Ppa/Pdp); si no, al guardar se reinserta. */
                        if (typeof gridFact !== 'undefined' && gridFact && gridFact.length && (oldPpa || oldPdp)) {
                            var ids = gridFact.jqGrid('getDataIDs') || [];
                            $.each(ids, function (_, id) {
                                var row = gridFact.jqGrid('getLocalRow', id) || gridFact.jqGrid('getRowData', id) || {};
                                var pdp = String(row['Pdp_Cod'] || '');
                                var ppa = String(row['Ppa_Cod'] || '');
                                var limpia = false;
                                if (oldPdp && oldPdp !== '0' && pdp === oldPdp) {
                                    limpia = true;
                                } else if (oldPpa && oldPpa !== '0' && ppa === oldPpa && (!pdp || pdp === '0')) {
                                    limpia = true;
                                }
                                if (limpia && typeof clearPpaRow === 'function') {
                                    clearPpaRow(id);
                                }
                            });
                        }
                        pintarChips();
                    };
                    window.aplicarRubroPresupuestoGeneral = function (g) {
                        if (!g || !((g.Pdp_Cod || g.Ppa_Cod) * 1)) {
                            window.limpiarCamposPresupuesto();
                            return;
                        }
                        $('#Pdp_Cod').val(g.Pdp_Cod || '');
                        $('#Ppa_Cod').val(g.Ppa_Cod || '');
                        $('#Ppa_Cla').val(g.Ppa_Cla || '');
                        $('#Ppa_Des').val(g.Ppa_Des || '');
                        $('#Ppa_Label').val(g.Ppa_Label || '');
                        $('#Ppa_Ruta').val(g.Ppa_Ruta || '');
                        pintarChips();
                    };
                    function setPpaHint(txt) {
                        $('#ppa_hint').html(txt || (ppaOrigen() === 'general'
                            ? 'Presupuesto General: rubros del catálogo que <b>no</b> pertenecen a proyectos.'
                            : (ppaSolRubrosActivo()
                                ? 'Presupuesto por Proyecto: filtre y pulse <b>buscar</b> para listar rubros.'
                                : 'Presupuesto por Proyecto: filtre por proyecto y pulse <b>buscar</b>.')));
                    }
                    function ppaOrigen() {
                        var v = ($('input[name=ppa_Origen]:checked').val() || 'proyecto');
                        return (v === 'solicitud') ? 'proyecto' : v;
                    }
                    function ppaSolRubrosActivo() {
                        if (typeof window.ppaSolRubrosOk === 'boolean') return window.ppaSolRubrosOk;
                        return String($('#ppaDialog').data('sol-rubros') || '0') === '1';
                    }
                    function limpiarGridPresupuesto() {
                        var $g = $('#containerPpa');
                        if (!$g.length || !$g[0].grid) return;
                        try {
                            if (typeof $g.clearGrid === 'function') {
                                $g.clearGrid();
                            } else if (typeof $g.jqGrid === 'function') {
                                $g.jqGrid('clearGridData', true);
                            }
                        } catch (e) { /* grid aún vacío */ }
                    }
                    function aplicarModoPresupuesto() {
                        var esProy = ppaOrigen() === 'proyecto';
                        $('.ppa-proy-only').toggleClass('is-off', !esProy);
                        limpiarGridPresupuesto();
                        if (esProy) {
                            var pro = $('#ppa_Pro_Cod').val() || '';
                            if (ppaSolRubrosActivo() && pro) {
                                cargarSolicitudesPresupuesto(pro, true);
                            } else if (ppaSolRubrosActivo()) {
                                resetSolicitudPresupuesto('Seleccione un proyecto...');
                            }
                            setPpaHint();
                        } else {
                            if (ppaSolRubrosActivo()) {
                                resetSolicitudPresupuesto('Solo en modo proyecto');
                            }
                            setPpaHint('Presupuesto General: pulse <b>buscar</b> para listar rubros fuera de proyectos.');
                        }
                    }
                    function resetSolicitudPresupuesto(msg) {
                        var $s = $('#ppa_Sol_Cod');
                        if (!$s.length || $s.is('input[type=hidden]')) return;
                        $s.prop('disabled', true)
                            .html('<option value="">' + (msg || 'Seleccione un proyecto...') + '</option>');
                        syncPpaSelectTitle($s);
                    }
                    function syncPpaSelectTitle($sel) {
                        var $s = $($sel);
                        if (!$s.length) return;
                        var $opt = $s.find('option:selected');
                        var full = $.trim(($opt.attr('title') || $opt.text() || ''));
                        $s.attr('title', full);
                        if ($.fn.tooltip && $s.data('ui-tooltip')) {
                            $s.tooltip('option', 'content', full || false);
                        }
                    }
                    function initPpaSelectTooltips() {
                        var $sels = $('#ppa_Pro_Cod').add($('#ppa_Sol_Cod').filter('select'));
                        if (!$sels.length) return;
                        $sels.each(function () { syncPpaSelectTitle(this); });
                        if (!$.fn.tooltip) return;
                        $sels.each(function () {
                            var $s = $(this);
                            if ($s.data('ui-tooltip')) return;
                            $s.tooltip({
                                items: 'select',
                                tooltipClass: 'ppa-tip',
                                content: function () {
                                    var $opt = $(this).find('option:selected');
                                    return $.trim($opt.attr('title') || $opt.text() || '') || false;
                                },
                                show: { delay: 200 },
                                hide: false,
                                position: { my: 'left top+4', at: 'left bottom', collision: 'flipfit' }
                            });
                        });
                    }
                    function llenarSelectSolicitudes(rows, placeholder) {
                        var $s = $('#ppa_Sol_Cod');
                        if (!$s.length || $s.is('input[type=hidden]')) return [];
                        $s.empty();
                        rows = $.isArray(rows) ? rows : [];
                        $s.append($('<option>', { value: '', text: rows.length ? (placeholder || 'Seleccione...') : 'Sin solicitudes' }));
                        $.each(rows, function (_, r) {
                            var lbl = r.Sol_Label || r.Sol_Num || '';
                            $s.append(
                                $('<option></option>')
                                    .val(r.Sol_Cod)
                                    .text(lbl)
                                    .attr('title', lbl)
                                    .attr('data-pro', r.Pro_Cod || '')
                            );
                        });
                        $s.prop('disabled', !rows.length);
                        syncPpaSelectTitle($s);
                        return rows;
                    }
                    function cargarSolicitudesPresupuesto(proCod, keepSol) {
                        if (!ppaSolRubrosActivo()) {
                            limpiarGridPresupuesto();
                            return;
                        }
                        if (ppaOrigen() !== 'proyecto') {
                            resetSolicitudPresupuesto('Solo en modo proyecto');
                            limpiarGridPresupuesto();
                            return;
                        }
                        var solPrev = keepSol ? ($('#ppa_Sol_Cod').val() || '') : '';
                        if (!proCod) {
                            resetSolicitudPresupuesto('Seleccione un proyecto...');
                            limpiarGridPresupuesto();
                            return;
                        }
                        resetSolicitudPresupuesto('Cargando...');
                        $.ajax({
                            url: window.location.pathname,
                            type: 'GET',
                            dataType: 'json',
                            data: { presupuestoSolicitudesAjax: 1, ppa_Pro_Cod: proCod },
                            success: function (res) {
                                if (!res || res.success === false) {
                                    resetSolicitudPresupuesto((res && res.message) ? res.message : 'Sin solicitudes');
                                    limpiarGridPresupuesto();
                                    return;
                                }
                                var rows = llenarSelectSolicitudes(res.response, 'Seleccione...');
                                if (solPrev && $sHasVal('#ppa_Sol_Cod', solPrev)) {
                                    $('#ppa_Sol_Cod').val(solPrev);
                                } else if (rows.length === 1) {
                                    $('#ppa_Sol_Cod').val(String(rows[0].Sol_Cod));
                                }
                                syncPpaSelectTitle('#ppa_Sol_Cod');
                                limpiarGridPresupuesto();
                                setPpaHint('Filtros listos. Pulse <b>buscar</b> para ver los rubros.');
                            },
                            error: function () {
                                resetSolicitudPresupuesto('Error al cargar solicitudes');
                                limpiarGridPresupuesto();
                            }
                        });
                    }
                    function $sHasVal(sel, val) {
                        var ok = false;
                        $(sel).find('option').each(function () {
                            if (String(this.value) === String(val)) { ok = true; return false; }
                        });
                        return ok;
                    }
                    window.buscarSolicitudPresupuesto = function () {
                        if (!ppaSolRubrosActivo()) {
                            setPpaHint('La búsqueda por solicitud no está disponible (falta <b>adq_solicitudes_rubros</b>).');
                            return;
                        }
                        if (ppaOrigen() !== 'proyecto') {
                            $('input[name=ppa_Origen][value=proyecto]').prop('checked', true);
                            aplicarModoPresupuesto();
                        }
                        var num = $.trim($('#ppa_Sol_Num').val() || '');
                        setPpaHint('Buscando solicitud...');
                        $.ajax({
                            url: window.location.pathname,
                            type: 'GET',
                            dataType: 'json',
                            data: { presupuestoSolicitudesAjax: 1, ppa_Sol_Num: num },
                            success: function (res) {
                                if (!res || res.success === false) {
                                    resetSolicitudPresupuesto((res && res.message) ? res.message : 'Sin solicitudes');
                                    setPpaHint('No se encontraron solicitudes con rubros.');
                                    limpiarGridPresupuesto();
                                    return;
                                }
                                var rows = llenarSelectSolicitudes(res.response, num ? 'Seleccione coincidencia...' : 'Seleccione...');
                                if (rows.length === 1) {
                                    $('#ppa_Sol_Cod').val(String(rows[0].Sol_Cod));
                                    if (rows[0].Pro_Cod && $sHasVal('#ppa_Pro_Cod', rows[0].Pro_Cod)) {
                                        $('#ppa_Pro_Cod').val(String(rows[0].Pro_Cod));
                                    }
                                    setPpaHint('Solicitud <b>' + ppaEsc(rows[0].Sol_Label || rows[0].Sol_Num) + '</b>. Pulse <b>buscar</b> para ver rubros.');
                                } else if (rows.length > 1) {
                                    $('#ppa_Pro_Cod').val('');
                                    setPpaHint('Hay <b>' + rows.length + '</b> solicitudes. Elija una y pulse <b>buscar</b>.');
                                } else {
                                    setPpaHint('No hay solicitudes con rubros de presupuesto para ese número.');
                                }
                                syncPpaSelectTitle('#ppa_Sol_Cod');
                                syncPpaSelectTitle('#ppa_Pro_Cod');
                                limpiarGridPresupuesto();
                            },
                            error: function () {
                                resetSolicitudPresupuesto('Error al cargar solicitudes');
                                setPpaHint('No se pudo buscar la solicitud.');
                                limpiarGridPresupuesto();
                            }
                        });
                    };
                    window.buscarPresupuesto = function () {
                        var $g = $('#containerPpa');
                        if (!$g.length || !$g[0].grid) return;
                        $('#frm_ppa').effect('highlight', {}, 400);
                        var origen = ppaOrigen();
                        var solCod = $('#ppa_Sol_Cod').val() || '';
                        var proCod = $('#ppa_Pro_Cod').val() || '';
                        if (origen === 'general') {
                            setPpaHint('Presupuesto General: rubros del catálogo que <b>no</b> pertenecen a proyectos.');
                        } else if (solCod) {
                            setPpaHint('Rubros de la <b>solicitud</b> seleccionada.');
                        } else if (proCod) {
                            setPpaHint('Rubros del <b>proyecto</b> seleccionado (sin solicitud).');
                        } else {
                            setPpaHint('Presupuesto por Proyecto: filtre por proyecto y/o No. de solicitud.');
                        }
                        $g.Search({
                            search: $.trim($('#ppa_search').val() || ''),
                            ppa_Origen: origen,
                            ppa_Pro_Cod: origen === 'proyecto' ? proCod : '',
                            ppa_Sol_Cod: origen === 'proyecto' ? solCod : ''
                        }, 'presupuestoRubrosAjax');
                    };
                    window.abrirBusquedaPresupuesto = function (fromRow) {
                        if (!fromRow) window.ppaRowIndex = null;
                        window.cerrarPpaRowPop();
                        $('#asigChips .exa-asig-chip').removeClass('active');
                        $('#asigFieldset .exa-asig-pop').not('#ppaRowPop').removeClass('open');
                        var $dlg = $('#ppaDialog');
                        if (!$dlg.length) return;
                        if (!$dlg.data('ui-dialog')) {
                            $dlg.dialog({
                                autoOpen: false,
                                modal: true,
                                width: 860,
                                height: 500,
                                resizable: true,
                                open: function () {
                                    setTimeout(function () { $('#ppa_Pro_Cod').focus(); }, 60);
                                    initPpaSelectTooltips();
                                    armarGridPresupuesto();
                                    aplicarModoPresupuesto();
                                    limpiarGridPresupuesto();
                                }
                            });
                            $('input[name=ppa_Origen]').on('change', function () {
                                aplicarModoPresupuesto();
                            });
                            $('#ppa_Pro_Cod').on('change', function () {
                                syncPpaSelectTitle(this);
                                limpiarGridPresupuesto();
                                if (ppaOrigen() === 'proyecto') {
                                    if (ppaSolRubrosActivo()) {
                                        cargarSolicitudesPresupuesto($(this).val());
                                    } else {
                                        setPpaHint('Presupuesto por Proyecto: pulse <b>buscar</b> para listar rubros del proyecto.');
                                    }
                                } else {
                                    setPpaHint('Presupuesto General: pulse <b>buscar</b> para listar rubros.');
                                }
                            });
                            if (ppaSolRubrosActivo()) {
                                $('#ppa_Sol_Cod').on('change', function () {
                                    syncPpaSelectTitle(this);
                                    var $opt = $(this).find('option:selected');
                                    var pro = $opt.data('pro');
                                    if (pro && $sHasVal('#ppa_Pro_Cod', pro)) {
                                        $('#ppa_Pro_Cod').val(String(pro));
                                        syncPpaSelectTitle('#ppa_Pro_Cod');
                                    }
                                    var txt = $.trim($opt.attr('title') || $opt.text() || '');
                                    if ($(this).val()) {
                                        setPpaHint('Solicitud <b>' + ppaEsc(txt) + '</b>. Pulse <b>buscar</b> para ver rubros.');
                                    } else {
                                        setPpaHint('Presupuesto por Proyecto: filtre y pulse <b>buscar</b> para listar rubros.');
                                    }
                                    limpiarGridPresupuesto();
                                });
                            }
                        }
                        $dlg.dialog('open');
                    };
                    window.selectPresupuestoRubro = function selectPresupuestoRubro(data) {
                        data = data || {};
                        var label = data['Ppa_Label'] || ((data['Ppa_Cla'] || '') + ' - ' + (data['Pdp_Rubro'] || data['Ppa_Des'] || ''));
                        var rowId = window.ppaRowIndex;
                        /* Selección desde fila del detalle documento. */
                        if (rowId && typeof gridFact !== 'undefined' && gridFact && gridFact.length) {
                            var rowData = {
                                Ppa_Cod: data['Ppa_Cod'] || '',
                                Pdp_Cod: data['Pdp_Cod'] || '',
                                Ppa_Cla: data['Ppa_Cla'] || '',
                                Ppa_Des: data['Ppa_Des'] || '',
                                Ppa_Label: label,
                                Ppa_Ruta: data['Ppa_Ruta'] || ''
                            };
                            gridFact.changeRowData(rowId, rowData);
                            if ($('#ppaDialog').data('ui-dialog')) $('#ppaDialog').dialog('close');
                            window.mostrarPpaRowPop($.extend({}, data, { Ppa_Label: label }), rowId);
                            return;
                        }
                        $('#Ppa_Cod').val(data['Ppa_Cod'] || '');
                        $('#Pdp_Cod').val(data['Pdp_Cod'] || '');
                        $('#Ppa_Cla').val(data['Ppa_Cla'] || '');
                        $('#Ppa_Des').val(data['Ppa_Des'] || '');
                        $('#Ppa_Label').val(label);
                        $('#Ppa_Ruta').val(data['Ppa_Ruta'] || '');
                        pintarChips();
                        if ($('#ppaDialog').data('ui-dialog')) $('#ppaDialog').dialog('close');
                    };
                    function ppaEsc(t) {
                        return $('<i/>').text(t == null ? '' : t).html().replace(/"/g, '&quot;');
                    }
                    /* Árbol indentado de los padres; el rubro en sí ya se ve en su columna/casillero. */
                    function rutaArbol(ruta) {
                        var txt = $.trim(ruta || ''), html = '';
                        if (txt === '') return '';
                        var padres = txt.split('>').slice(0, -1);
                        if (!padres.length) return '<span class="exa-sinpadre">— sin grupo —</span>';
                        $.each(padres, function (i, p) {
                            var directo = (i === padres.length - 1);
                            html += '<span class="n' + (directo ? ' pad' : '') + '" style="padding-left:' + (i * 11) + 'px">'
                                + (i > 0 ? '<span class="cn">\u2514 </span>' : '')
                                + ppaEsc($.trim(p)) + '</span>';
                        });
                        return '<span class="exa-arbol" title="' + ppaEsc(txt) + '">' + html + '</span>';
                    }
                    function armarGridPresupuesto() {
                        var $g = $('#containerPpa');
                        if (!$g.length || $g[0].grid || typeof $g.createGrid !== 'function') return;
                        $g.createGrid({
                            width: 820,
                            height: 310,
                            colModel: [
                                { name: 'Pdp_Cod', hidden: true },
                                { name: 'Ppa_Cod', hidden: true },
                                { label: 'Código', name: 'Ppa_Cla', width: 80 },
                                { label: 'Rubro', name: 'Pdp_Rubro', width: 150 },
                                { label: 'Descripción', name: 'Ppa_Des', width: 150 },
                                { label: 'Ruta', name: 'Ppa_Ruta', width: 250, classes: 'ppa-arbol', title: false, formatter: rutaArbol },
                                {
                                    label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false,
                                    formatter: 'gridButton',
                                    /* action va por nombre: gridButton arma el onclick con esa cadena. */
                                    formatoptions: { action: 'selectPresupuestoRubro', title: 'Seleccionar rubro' }
                                }
                            ],
                            jsonReader: { root: 'response', repeatitems: false },
                            datatype: 'local',
                            footerrow: false
                        });
                    }
                    $(function () {
                        /* El diálogo y el pop de fila salen del form del documento. */
                        $('#ppaDialog,#ppaRowPop').appendTo('body');
                        var $fs = $('#asigFieldset');
                        if (!$fs.length) return;
                        $('#asigChips').on('click', '.exa-asig-chip', function () {
                            var $chip = $(this), abierto = $chip.hasClass('active'), tipo = $chip.data('asig');
                            cerrarPop();
                            if (abierto) return;
                            /* Presupuesto sin rubro: va directo al modal de búsqueda. */
                            if (tipo === 'pre' && valorDe('pre') === '') {
                                abrirBusquedaPresupuesto();
                                return;
                            }
                            var $pop = (tipo === 'pre') ? $('#formPre') : $fs.find('.exa-asig-pop[data-asig=' + tipo + ']').not('#ppaRowPop');
                            if (!$pop.length) return;
                            $chip.addClass('active');
                            var pos = $chip.position();
                            $pop.addClass('open').css({ top: pos.top + $chip.outerHeight() + 2, left: Math.max(0, pos.left) });
                            if ($pop.offset().left + $pop.outerWidth() > $fs.offset().left + $fs.outerWidth()) {
                                $pop.css('left', Math.max(0, $fs.outerWidth() - $pop.outerWidth() - 4));
                            }
                            $pop.find('select,input:not([type=hidden])').first().focus();
                        });
                        $fs.on('change', 'select', function () {
                            pintarChips();
                            cerrarPop();
                        });
                        $(document).on('mousedown.asig', function (ev) {
                            if ($(ev.target).closest('#asigFieldset, #ppaRowPop, .ui-dialog, .ui-autocomplete').length) return;
                            cerrarPop();
                        });
                        $(document).on('keydown.asig', function (ev) {
                            if (ev.keyCode === 27) cerrarPop();
                        });
                        setInterval(pintarChips, 600);
                        pintarChips();
                    });
                })();
            </script>

        </div>
        <div class="col-xs-7">
            <fieldset class="exa-fieldset" id="docuFormTemp">
                <legend class="Titulos2">Datos del Documento</legend>
                <input type="text" name="Cop_Cod" style="display: none;" />
                <input type="text" name="Com_Cod" style="display: none;" />
                <div class="row">
                    <div class="col-xs-12">
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs required">Sustento:</label>
                            <div class="col-xs-10">
                                <?php $rs_sustento = $obBD_con1->getArrayConsulta('sustento.selectWhere', array('clean' => true, 'where' => array('Tri_Est' => 'A')), $obBD_conexion); ?>
                                <select id="Tri_Cod" name="Tri_Cod" class="form-control input-xs" tabindex="3" onchange="tipoComprobanteHide($('option:selected', this).attr('data-ticsri')); if($('option:selected', this).attr('data-ticsri')=='10'){ cambioTipoDoc($('#Tic_Cod').val()); }" required="">
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($rs_sustento as $row) {
                                        echo "<option value='{$row['Tri_Cod']}' " . ($row['Tri_Cod'] == 2 ? 'selected' : '') . " data-ticsri='" . $row['Tri_Sri'] . "' >" . utf8_encode($row['Tri_Sri']) . "-" . utf8_encode($row['Tri_Des']) . "     </option>";
                                    } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs required">Documento:</label>
                            <div class="col-xs-5">
                                <div class="input-group">
                                    <select id="Tic_Cod" name="Tic_Cod" class="form-control input-xs" tabindex="4" onchange="validaCopNum();cambioTipoDoc(this.value);if(typeof window.toggleBtnIngresoAutLiq==='function'){window.toggleBtnIngresoAutLiq();}" required="" data-trigger="">
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($rs_tip_compr as $row) {
                                            if ($row['Tic_Sri'] != 4 && $row['Tic_Sri'] != 5 && $row['Tic_Sri'] != 7 && $row['Tic_Sri'] != 23 && $row['Tic_Sri'] != 24)
                                                //echo "<option value='$row[Tic_Cod]' data-ticsri='$row[Tic_Sri]'>$row[Tic_Sri] - $row[Tic_Des]</option>";
                                                echo "<option value='{$row['Tic_Cod']}' data-ticsri='" . utf8_encode($row['Tic_Sri']) . "'>" . utf8_encode($row['Tic_Sri']) . " - " . utf8_encode($row['Tic_Des']) . "</option>";
                                        } ?>
                                    </select>
                                    <span class="input-group-btn hidden" id="btnIngresoAutLiq">
                                        <button type="button" class="btn btn-info btn-xs" title="Ingresar autorización externa de liquidación de compra" onclick="validaIngresoAut()"><i class="glyphicon glyphicon-edit"></i></button>
                                    </span>
                                    <script>
                                    (function () {
                                        if (typeof window.esLiquidacionCompraTic !== 'function') {
                                            window.esLiquidacionCompraTic = function () {
                                                var $tic = window.jQuery ? jQuery('#Tic_Cod') : null;
                                                if (!$tic || !$tic.length) return false;
                                                var val = String($tic.val() || '');
                                                if (val === '3' || val * 1 === 3) return true;
                                                var sel = $tic.find('option:selected');
                                                var sri = parseInt(sel.attr('data-ticsri'), 10);
                                                if (!isNaN(sri) && sri === 3) return true;
                                                sri = parseInt(sel.data('ticsri'), 10);
                                                return !isNaN(sri) && sri === 3;
                                            };
                                        }
                                        if (typeof window.toggleBtnIngresoAutLiq !== 'function') {
                                            window.toggleBtnIngresoAutLiq = function () {
                                                var $btn = window.jQuery ? jQuery('#btnIngresoAutLiq') : null;
                                                if (!$btn || !$btn.length) return;
                                                $btn.toggleClass('hidden', !window.esLiquidacionCompraTic());
                                            };
                                        }
                                    })();
                                    </script>
                                </div>

                            </div>
                            <label class="col-xs-2 control-label label-xs required">Emision:</label>
                            <div class="col-xs-3">
                                <div class="input-group">
                                    <input id="Cop_Fec" name="Cop_Fec" type="text" class="form-control input-xs datepickers" tabindex="8" required="" />
                                    <span class="input-group-addon input-xs" title="Fecha de Emisión del Proveedor"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                </div>
                            </div>
                            <input type="hidden" id="idCargaExitosa" name="idCargaExitosa">
                        </div>
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs required">Número:</label>
                            <div class="col-xs-5">
                                <div class="input-group input-group-xs">

                                    <span id="Pun_Sri" name="Pun_Sri" class="input-group-addon alert-info"></span>
                                    <input type="text" name="Pun_Sri" id="Pun_Sri" style="display:none;">

                                    <input type="text" id="Cop_Num" name="Cop_Num" onchange="validaCopNum()" class="form-control input-xs secuencia" tabindex="5" required="" />
                                    <span class="input-group-addon validate"><i></i></span>
                                </div>
                            </div>
                            <input type="text" name="Aut_Codliq" style="display: none;" id="Aut_Codliq" />
                            <label class="col-xs-2 control-label label-xs required">Impresión:</label>
                            <div class="col-xs-3">
                                <div class="input-group">
                                    <input id="Cop_Imf" name="Cop_Imf" type="text" class="form-control input-xs datepickers empty" tabindex="9" required="" />
                                    <span class="input-group-addon input-xs" title="Fecha de Creación en Imprenta"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs required">Autoriza:</label>
                            <div class="col-xs-5">
                                <div class="input-group input-group-xs">
                                    <input id="Cop_Aut" type="text" name="Cop_Aut" class="form-control datatitle datatrigger" tabindex="6" required="" maxlength="49" pattern="\d{10}|\d{37}|\d{49}" />
                                    <span class="input-group-addon validate"><i></i></span>
                                </div>
                            </div>

                            <label class="col-xs-2 control-label label-xs required">Caducidad:</label>
                            <div class="col-xs-3">
                                <div class="input-group">
                                    <input id="Cop_Cad" name="Cop_Cad" type="text" class="form-control input-xs datepickers empty" tabindex="10" required="" />
                                    <span class="input-group-addon input-xs" title="Fecha de Caducidad en el SRI"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-2 control-label label-xs required">Ciudad:</label>
                            <div class="col-xs-5">
                                <?php $rs_ciudad = $obBD_con1->getArrayConsulta('ciudad.selectWhere', array('clean' => true, 'join' => array('provincia' => array('on' => 'provincia.Pro_Cod=ciudad.Pro_Cod', 'cols' => 'Pro_Nom')), 'where' => "Ciu_Des != ''", 'order' => 'Ciu_Des'), $obBD_conexion); ?>
                                <select name="Ciu_Cod" id="Ciu_Cod" class="form-control input-xs" data-placeholder="Seleccione..." tabIndex="7">
                                    <option value=""></option>
                                    <?php foreach ($rs_ciudad as $row) {
                                        //echo "<option value='$row[Ciu_Cod]' data-prov='$row[Pro_Nom]'>$row[Ciu_Des]</option>";
                                        echo "<option value='{$row['Ciu_Cod']}' data-prov='" . utf8_encode($row['Pro_Nom']) . "'>" . utf8_encode($row['Ciu_Des']) . "</option>";
                                    } ?>
                                </select>
                            </div>
                            <?php if ($configs['Cof_Con'] == 'S') { ?>
                                <label class="col-xs-2 control-label label-xs required">Comprobante:</label>
                                <div class="col-xs-3">
                                    <div class="input-group">
                                        <input id="Com_Fec" name="Com_Fec" type="text" class="form-control input-xs datepickers" tabindex="11" required="" />
                                        <span class="input-group-addon input-xs" title="Fecha del Comprobante de Egreso/Diario"><i class="glyphicon glyphicon-info-sign blue"></i></span>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                                </div>
            </fieldset>
        </div>
    </div>
</form>
<div class="row gridProductosCalculo">
    <div class="col-xs-12" style="min-height: 200px; padding-bottom: 5px;">
        <table id="documento"></table>
        <div id="documentoPager"></div>
    </div>
</div>
<div class="row form-horizontal normal">
    <div class="col-xs-6">
        <fieldset class="exa-fieldset">
            <legend class="Titulos2">Datos de la Retención</legend>
            <form id="reteFormTemp" action="javascript:" class="formDatos">
                <input type="text" name="Ret_Cod" style="display: none;" id="Ret_Cod" />
                <input type="text" name="Ret_Xml" style="display: none;" />
                <input type="text" name="Aut_Cod" style="display: none;" id="Aut_Cod_Old" />

                <div class="form-group">
                    <?php if ($rs_infoEmpresa['Ret_Scom'] == "S") { ?>
                        <div class="col-xs-12 control-label label-xs text-success" style="padding:10px;">
                            <small>Tiene activada la opción de generar retención sin comprobante</small>
                        </div>
                    <?php }  ?>

                    <label class="col-xs-1 control-label label-xs required">Número:</label>
                    <div class="col-xs-3">
                        <input type="text" name="Aut_Tem" style="display: none;" />
                        <div class="input-group input-group-xs">
                            <input id="Ret_Num" name="Ret_Num" type="text" class="form-control input-xs readOnly ret_field numeric" onchange="validaRetNum()" required="" />
                            <span class="input-group-addon validate"><i></i></span>
                            <span class="input-group-btn">
                                <button id="btnClaveExterna" type="button" onclick="changeElect()" class="btn btn-success btn-xs" title="Retencion Electronica Externa" tabindex="-1" style="display: none;"><span class="fa fa-globe"></span></button>
                            </span>
                        </div>
                    </div>
                    <script>
                        function fecRetencionServidor() {
                            var v = $('#Ret_Fec').attr('data-fec-servidor');
                            return (v && /^\d{4}-\d{2}-\d{2}/.test(String(v))) ? String(v).substring(0, 10) : '';
                        }
                        function fechaDesdeClaveAccesoSri(clave) {
                            clave = String(clave || '').replace(/\D/g, '');
                            if (clave.length < 8) return '';
                            var dd = clave.substr(0, 2), mm = clave.substr(2, 2), yyyy = clave.substr(4, 4);
                            var d = parseInt(dd, 10), m = parseInt(mm, 10), y = parseInt(yyyy, 10);
                            if (isNaN(d) || isNaN(m) || isNaN(y) || y < 2000 || m < 1 || m > 12 || d < 1 || d > 31) return '';
                            var dt = new Date(y, m - 1, d);
                            if (dt.getFullYear() !== y || dt.getMonth() !== (m - 1) || dt.getDate() !== d) return '';
                            return yyyy + '-' + mm + '-' + dd;
                        }
                        function maxFechaRetencionCompra(copFec) {
                            if (!copFec || !/^\d{4}-\d{2}-\d{2}/.test(String(copFec))) return '';
                            var p = String(copFec).substring(0, 10).split('-');
                            var d = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
                            if (isNaN(d.getTime())) return '';
                            d.setDate(d.getDate() + 8);
                            var mm = d.getMonth() + 1, dd = d.getDate();
                            return d.getFullYear() + '-' + (mm < 10 ? '0' : '') + mm + '-' + (dd < 10 ? '0' : '') + dd;
                        }
                        function flyoutRetFec(msg) {
                            var $r = $('#Ret_Fec');
                            if ($r.createFlyout) {
                                $r.createFlyout(msg, { icon: 'exclamation', placement: 'right_bottom' });
                                $r.flyout('show');
                            } else if ($.alert) {
                                $.alert(msg, null, 'remove');
                            }
                        }
                        function limpiarRetFecInvalida(msg) {
                            $('#Ret_Fec').val('');
                            if (msg) flyoutRetFec(msg);
                            return false;
                        }
                        /**
                         * Valida Ret_Fec vs fecha de compra y, si hay clave externa, vs fecha de la clave (DDMMYYYY).
                         * Si no es válida, limpia el input.
                         * @returns {boolean}
                         */
                        function validarRetFecCompraYClave(opts) {
                            opts = opts || {};
                            var Ret_Fec = $.trim($('#Ret_Fec').val() || '');
                            var Cop_Fec = $.trim($('#Cop_Fec').val() || '').substring(0, 10);
                            if (!Ret_Fec) return true;

                            if (Cop_Fec) {
                                if (Ret_Fec < Cop_Fec) {
                                    return limpiarRetFecInvalida('La fecha de retención no puede ser menor a la fecha de compra (<u class="orange">' + Cop_Fec + '</u>).');
                                }
                                var maxRet = maxFechaRetencionCompra(Cop_Fec);
                                if (maxRet && Ret_Fec > maxRet) {
                                    return limpiarRetFecInvalida('La fecha de retención no puede ser mayor a <u class="orange">' + maxRet + '</u> (máx. 8 días desde la compra).');
                                }
                            }

                            var claveActiva = ($('#isClaveExterna').val() === '1') || $('.claveExterna').is(':visible');
                            var fecClave = fechaDesdeClaveAccesoSri($('#claveAccesoExt').val());
                            if (claveActiva && fecClave && Ret_Fec !== fecClave) {
                                return limpiarRetFecInvalida('La fecha de retención debe coincidir con la de la clave de acceso (<u class="orange">' + fecClave + '</u>).');
                            }
                            return true;
                        }
                        function aplicarFechaRetencionDesdeClaveExt() {
                            var clave = $('#claveAccesoExt').val();
                            var fec = fechaDesdeClaveAccesoSri(clave);
                            if (!fec) return;
                            $('#Ret_Fec').val(fec);
                            if (!validarRetFecCompraYClave()) {
                                return;
                            }
                            if (typeof validaRetFec === 'function') validaRetFec();
                        }
                        function initRetFecServidor() {
                            var fecSrv = fecRetencionServidor();
                            if (fecSrv && !$.trim($('#Ret_Fec').val())) {
                                $('#Ret_Fec').val(fecSrv);
                            }
                        }
                        window.fecRetencionServidor = fecRetencionServidor;
                        window.fechaDesdeClaveAccesoSri = fechaDesdeClaveAccesoSri;
                        window.validarRetFecCompraYClave = validarRetFecCompraYClave;
                        window.aplicarFechaRetencionDesdeClaveExt = aplicarFechaRetencionDesdeClaveExt;
                        function changeElect() {
                            $('.claveExterna').toggleCss('display', 'none');
                            $('#claveAccesoExt').toggleAttr('required');
                            $('#claveAccesoExt').val("");
                            $("#isClaveExterna").val($('.claveExterna').is(":visible") ? "1" : "");
                            if ($('.claveExterna').is(":visible")) {
                                initRetFecServidor();
                                setTimeout(function () { $('#claveAccesoExt').trigger('focus'); }, 50);
                            }
                        }
                        jQuery(function () {
                            initRetFecServidor();
                        });
                    </script>
                    <label class="col-xs-1 control-label label-xs required">Autoriza:</label>
                    <?php if (!isset($insert)) { ?>
                        <div class="col-xs-3">
                            <div class="input-group input-group-xs">
                                <span name="Aut_Sri" class="form-control input-xs databind"></span>
                                <span class="input-group-btn"><button type="button" onclick="$('#autorizaDialog').dialog('open');" class="btn btn-success btn-xs" title="Cambiar Autorización" tabindex="-1"><span class="glyphicon glyphicon-transfer"></span></button></span>
                                <span id="Aut_Cod_tip" class="input-group-addon input-xs" style="cursor:help;" title="Cód.Int. autorización"><i class="glyphicon glyphicon-info-sign blue" style="vertical-align:middle;" aria-hidden="true"></i></span>
                                <span id="Aut_Cod" style="display:none !important;" aria-hidden="true"></span>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="col-xs-3">
                            <div class="input-group input-group-xs">
                                <span name="Aut_Sri" class="form-control input-xs"></span>
                                <span id="Aut_Cod_tip" class="input-group-addon input-xs" style="cursor:help;" title="Cód.Int. autorización"><i class="glyphicon glyphicon-info-sign blue" style="vertical-align:middle;" aria-hidden="true"></i></span>
                                <span id="Aut_Cod" style="display:none !important;" aria-hidden="true"></span>
                            </div>
                        </div>
                    <?php } ?>
                    <script>
                    (function () {
                        function syncAutCodTip() {
                            var el = document.getElementById('Aut_Cod');
                            var tip = document.getElementById('Aut_Cod_tip');
                            if (!el || !tip) return;
                            var v = (el.textContent || '').replace(/\s+/g, '').trim();
                            tip.setAttribute('title', v ? ('Cód.Int.: ' + v) : 'Cód.Int. autorización');
                            if (!tip.querySelector('i.glyphicon-info-sign')) {
                                tip.innerHTML = '<i class="glyphicon glyphicon-info-sign blue" aria-hidden="true"></i>';
                            }
                        }
                        window.setAutCodTip = function (v) {
                            v = (v == null || v === '') ? '' : String(v);
                            var el = document.getElementById('Aut_Cod');
                            if (el) el.textContent = v;
                            syncAutCodTip();
                        };
                        if (window.jQuery) {
                            jQuery(function () {
                                var el = document.getElementById('Aut_Cod');
                                if (!el || el._autCodObs) return;
                                el._autCodObs = true;
                                syncAutCodTip();
                                if (window.MutationObserver) {
                                    new MutationObserver(syncAutCodTip).observe(el, { childList: true, characterData: true, subtree: true });
                                }
                            });
                        }
                    })();
                    </script>
                    <div class="col-xs-3 text-right">
                        <?php if ($configs['Cof_Con'] == 'S') { ?>
                            <?php $row_rs_RetPld = $obBD_con1->getArrayConsulta(67, $Ses_Emp_Cod . '*' . 'RA', $obBD_conexion); ?>
                            <div id="asumirRet" style="display:none;">
                                <input type="text" name="Ret_Pld_Cod" value="<?php if (!empty($row_rs_RetPld)) echo $row_rs_RetPld[0]['Pld_Cod']; ?>" style="display: none" />
                                <input type="checkbox" id="Ret_Asu" name="Ret_Asu" value="S" offval="N" <?php if (empty($row_rs_RetPld)) echo 'disabled="disabled" title="No se ha parametrizado una cuenta contable."'; ?>><label class="control-label label-xs">&nbsp;&nbsp;Asumir Retención <i class="glyphicon glyphicon-info-sign blue" title="<?php if (empty($row_rs_RetPld)) echo 'No se ha parametrizado una cuenta contable.';
                                                                                                                                                                                                                                                                                                                                                            else echo 'Asumir el Valor de la Retención Contablemente'; ?>"></i></label>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-1 control-label label-xs required">Fecha:</label>
                    <div class="col-xs-3">
                        <div class="input-group input-group-xs">
                            <span class="input-group-addon input-xs" title="Editar fecha retención"><input type="checkbox" id="edit_fec_ret" name="edit_fec_ret" title="Editar la fecha de la retención." style="margin:0;vertical-align:middle;"></span>
                            <input id="Ret_Fec" name="Ret_Fec" type="text" class="form-control input-xs readOnly ret_field datepickers" required="" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" onchange="validaRetFec()" value="<?php echo htmlspecialchars($hoy, ENT_QUOTES, 'UTF-8'); ?>" data-fec-servidor="<?php echo htmlspecialchars($hoy, ENT_QUOTES, 'UTF-8'); ?>" />
                            <span class="input-group-addon input-xs" title="Fecha de la Retención"><i class="glyphicon glyphicon-info-sign blue" style="vertical-align:middle;"></i></span>
                        </div>
                    </div>
                    <div class="col-xs-7 reteTot">
                        <div class="input-group input-group-xs">
                            <span class="input-group-addon bold alert-info">Renta:</span>
                            <input name="Ret_Ren_Tot" type="text" class="form-control span" style="text-align: right;" readonly="" />
                            <span class="input-group-addon bold alert-info">+&nbsp;IVA:</span>
                            <input name="Iva_Ren_Tot" type="text" class="form-control span" style="text-align: right;" readonly="" />
                            <span class="input-group-addon bold alert-info">=&nbsp;Retenido:</span>
                            <input id="Ren_Tot" name="Ren_Tot" type="text" class="form-control span" style="text-align: right;" readonly="" />
                        </div>
                    </div>
                </div>
                <div class="form-group claveExterna" style="display: none;">
                    <div class="col-xs-2"><input type="hidden" id="isClaveExterna" name="isClaveExterna" value=""></div>
                    <div class="col-xs-10">
                        <div class="input-group input-group-xs">
                            <span class="input-group-addon bold alert-info">Clave Acceso:</span>
                            <input type="text" id="claveAccesoExt" name="claveAccesoExt" onkeypress="return validar_numeric(event);" oninput="if(String(this.value||'').replace(/\D/g,'').length>=8)aplicarFechaRetencionDesdeClaveExt();" onchange="$(this).prop('title', this.value);aplicarFechaRetencionDesdeClaveExt();" minlength="49" maxlength="49" class="form-control" />
                        </div>
                    </div>
                </div>
                <div class="form-group reteTot cod_banano" style="display:none;">
                    <label class="col-xs-2 control-label label-xs required">Banano:</label>
                    <div class="col-xs-8">
                        <div class="input-group input-group-xs">
                            <span class="input-group-addon bold alert-warning">&nbsp;Cod. 338&nbsp;&nbsp;<i class="glyphicon glyphicon-arrow-right"></i>&nbsp;</span>
                            <span class="input-group-addon bold alert-success" title="Cajas de Banano">Cajas:</span>
                            <input name="Ret_Uca" type="text" class="form-control span" style="text-align: right;" pattern="\d*" placeholder="0" />
                            <span class="input-group-addon bold alert-success" title="Precio Unitario por Caja">P.Unit.:</span>
                            <input name="Ret_Pca" type="text" class="form-control span" style="text-align: right;" pattern="\d*" placeholder="0.00" />
                        </div>
                    </div>
                </div>
                <div class="form-group reteTot">
                    <label class="col-xs-5 control-label label-xs"></label>
                    <div class="col-xs-7">
                        <div class="input-group input-group-sm">
                            <span class="input-group-addon bold alert-warning">A Pagar&nbsp;&nbsp;<i class="glyphicon glyphicon-arrow-right"></i></span>
                            <span class="input-group-addon bold alert-success"><i class="glyphicon glyphicon-usd"></i></span>
                            <input id="Val_Pcc" name="Val_Pcc" type="text" class="form-control bold span" style="text-align: right;font-size: 15px; background-color: white;" readonly="" />
                            <span id="infoLiquida" class="input-group-addon validate" style="display:none;"><i></i></span>
                            <span class="input-group-btn">
                                <button type="button" onclick="$('#retDetaDialog').dialog('open')" class="btn btn-info" title="Ver Detalle Retención" tabindex="-1"><span class="glyphicon glyphicon-eye-open"></span></button>
                            </span>
                        </div>
                    </div>
                </div>
            </form>
        </fieldset>
    </div>
    <div class="col-xs-6 gridProductosCalculo">
        <fieldset class="exa-fieldset">
            <legend class="Titulos2">Forma de Pago</legend>
            <form id="pagoFormTemp" action="javascript:" class="formDatos">
                <input type="text" name="Cpp_Cod" style="display: none;" />
                <div class="form-group pagoSri">
                    <label class="col-xs-2 control-label label-xs required">Pago&nbsp;SRI:</label>
                    <div class="col-xs-7">
                        <?php $rs_pag_sri = $obBD_con1->getArrayConsulta('tipopagocom.selectWhere', array('clean' => true, 'where' => array('Tpc_Est' => 'A')), $obBD_conexion); ?>
                        <select id="Tpc_Cod" name="Tpc_Cod" class="form-control input-xs readOnly">
                            <option value="">Seleccione...</option>
                            <?php foreach ($rs_pag_sri as $row) {
                                //echo "<option value='$row[Tpc_Cod]' >$row[Tpc_Sri] - $row[Tpc_Des]</option>";
                                echo "<option value='" . $row['Tpc_Cod'] . "' " . $selected .  " >" . utf8_encode($row['Tpc_Sri']) . " - " . utf8_encode($row['Tpc_Des']) . "</option>";
                            } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs required">Forma:</label>
                    <div class="col-xs-3">
                        <?php $rs_forma = $obBD_con1->getArrayConsulta('forma_pago.selectWhere', array('clean' => true, 'where' => array('For_Est' => 'A'), 'order' => 'For_Des'), $obBD_conexion); ?>
                        <select id="For_Cod" name="For_Cod" class="form-control input-xs readOnly" data-trigger="" onchange="checkCuentaPago();" required="">
                            <option value="">Seleccione...</option>
                            <?php foreach ($rs_forma as $row) {
                                echo "<option value='$row[For_Cod]' " . ($row['For_Des'] == 'Contado' ? "selected=''" : '') . ">$row[For_Des]</option>";
                            } ?>
                            <option value="3">Caja Chica</option>
                        </select>
                    </div>
                    <?php if ($configs['Cof_Con'] == 'S') { ?>
                        <label class="col-xs-2 control-label label-xs required">Cuenta:</label>
                        <div class="col-xs-5">
                            <select id="Pag_Pld" name="Pag_Pld" class="form-control input-xs readOnly" required=""></select>
                        </div>
                    <?php } ?>
                </div>
                <div class="form-group pagoCredito" style="display: none;">
                    <input type="text" name="Cpp_Min" style="display:none" />
                    <label class="col-xs-2 control-label label-xs required">Vencimiento:</label>
                    <div class="col-xs-4">
                        <div class="input-group input-group-xs">
                            <input id="Cpp_Ven_plazo" type="number" min="1" step="1" class="form-control input-xs" placeholder="Días plazo" title="" />
                            <span class="input-group-addon input-xs" title="Sumar días a la fecha de compra"><i class="glyphicon glyphicon-arrow-right"></i></span>
                            <input id="Cpp_Ven" name="Cpp_Ven" type="text" class="form-control input-xs datepickers" />                            
                        </div>
                    </div>
                    <label class="col-xs-2 control-label label-xs">Observación:</label>
                    <div class="col-xs-4">
                        <textarea name="Cpp_Obs" class="form-control input-xs"></textarea>
                    </div>
                </div>
            </form>
        </fieldset>
        <fieldset class="exa-fieldset" id="gridReembolsos">
            <legend class="Titulos2">Facturas a Reembolsar</legend>
            <div class="condensed">
                <table id="reembolsos"></table>
                <div id="reembolsosPager"></div>
            </div>
        </fieldset>
    </div>
</div>
<!-- DIALOGO DETALLE RETENCION -->
<div id="retDetaDialog" title="Retención">
    <div class="condensed-header">
        <table id="retencion"></table>
    </div>
</div>