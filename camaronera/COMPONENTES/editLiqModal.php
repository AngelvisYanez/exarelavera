 <form id="frm_Edit_Liq" name="frm_Edit_Liq" class="form-horizontal normal" action="javascript:validaDocumentEditLiq();">
     <div class="col-xs-12">
         <fieldset class="exa-fieldset">
             <div class="form-group">
                 <label class="col-xs-1 control-label label-xs">Fecha:</label>
                 <div class="col-xs-3">
                     <input name="Fec_Neg" id="Fec_Neg" type="date" value="<?php echo  date("Y-m-d") ?>" class="form-control input-xs ">
                 </div>
                 <label class="col-xs-2 control-label label-xs">Nro.Liq:</label>
                 <div class="col-xs-2">
                     <input name="Num_Liq" id="Num_Liq" type="text" class="form-control input-xs" readonly>
                 </div>
                 <input type="hidden" name="Cod_Neg" id="Cod_Neg">
                 <label class="col-xs-2 control-label label-xs">Nro.Nego:</label>
                 <div class="col-xs-2">
                     <input name="Num_Neg" id="Num_Neg" type="text" class="form-control input-xs" readonly>
                 </div>
             </div>
         </fieldset>
     </div>
     <div class="col-xs-12">
         <div class="form-group">
             <label class="col-xs-3 control-label label-xs">Seleccionar Aguaje:</label>
             <div class="col-xs-9"><select name="Cod_Agu" id="Cod_Agu" class="form-control input-xs" onchange="loadDataLiq('editLiq')"> </select>
             </div>
         </div>
     </div>
     <div class="col-xs-6">
         <fieldset class="exa-fieldset">
             <legend class="Titulos2">Productor:</legend>
             <div class="form-group col-xs-12">
                 <div class="form-group">
                     <input type="hidden" name="Prod_Cod" id="Prod_Cod" class="form-control input-xs">
                     <label class="col-xs-3 control-label label-xs required">Nombre:</label>
                     <div class="col-xs-9">
                         <div class="input-group input-group-xs">
                             <input name="productor" id="productor" type="text" class="form-control input-xs ">
                             <span class="input-group-btn">
                                 <button id="Prv_Btn" type="button" onclick="$('#prodDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Productor" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                             </span>
                         </div>
                     </div>
                 </div>
                 <div class="form-group">
                     <label class="col-xs-3 control-label label-xs">Telf: </label>
                     <div class="col-xs-9"><input name="Telf_Prod" id="Telf_Prod" type="text" class="form-control input-xs" readonly></div>
                 </div>
                 <div class="form-group">
                     <label class="col-xs-3 control-label label-xs">Dirección: </label>
                     <div class="col-xs-9"><input name="Prs_Dir" id="Prs_Dir" type="text" class="form-control input-xs" readonly></div>
                 </div>
             </div>
         </fieldset>
     </div>
     <div class="col-xs-6">
         <fieldset class="exa-fieldset">
             <legend class="Titulos2">Empacadora:</legend>
             <div class="form-group">
                 <input type="text" id="Empa_Cod" name="Empa_Cod" hidden>
                 <label class="col-xs-3 control-label label-xs">Nombre:</label>
                 <div class="col-xs-9">
                     <input type="text" name="Nom_Emp" id="Nom_Emp" class="form-control input-xs" readonly>
                 </div>
             </div>
             <div class="form-group">
                 <label class="col-xs-3 control-label label-xs">Dirección:</label>
                 <div class="col-xs-9">
                     <input type="text" name="Dir_Emp" id="Dir_Emp" class="form-control input-xs" readonly>
                 </div>
             </div>
             <div class="form-group">
                 <label class="col-xs-3 control-label label-xs">Ciudad:</label>
                 <div class="col-xs-9">
                     <input type="text" name="Ciu" id="Ciu" class="form-control input-xs" readonly>
                 </div>
             </div>
         </fieldset>
     </div>

     <div class="col-xs-12">
         <fieldset class="exa-fieldset">
             <legend class="Titulos2">Datos liquidación:</legend>
             <div class="col-xs-6">
                 <div class="form-group">
                     <label class="col-xs-6 control-label label-xs">Fecha Ingreso:</label>
                     <div class="col-xs-6">
                         <input type="date" name="Liq_Fecha" id="Liq_Fecha" value="<?php echo  date("Y-m-d") ?>" class="form-control input-xs ">
                         <input type="hidden" name="Liq_Cod" id="Liq_Cod">
                     </div>
                 </div>
                 <div class="form-group">
                     <label class="col-xs-6 control-label label-xs">Peso remitido:</label>
                     <div class="col-xs-6"><input name="Peso_Rem" id="Peso_Rem" type="number" step="any" class="form-control input-xs" placeholder="0.00"></div>
                 </div>
                 <div class="form-group">
                     <label class="col-xs-6 control-label label-xs">Peso planta:</label>
                     <div class="col-xs-6"><input name="Peso_Planta" id="Peso_Planta" type="number" step="any" class="form-control input-xs" placeholder="0.00"></div>
                 </div>
                 <div class="form-group">
                     <label class="col-xs-6 control-label label-xs">Lib. faltantes:</label>
                     <div class="col-xs-6"><input name="Lib_Falt" id="Lib_Falt" type="number" step="any" class="form-control input-xs" placeholder="0.00"></div>
                 </div>
                 <div class="form-group">
                     <label class="col-xs-6 control-label label-xs">Basura:</label>
                     <div class="col-xs-6"><input name="Basur" id="Basur" type="number" step="any" class="form-control input-xs" placeholder="0.00"></div>
                 </div>
                 <div class="form-group">
                     <label class="col-xs-6 control-label label-xs">Peso neto:</label>
                     <div class="col-xs-6"><input name="Peso_Net" id="Peso_Net" type="number" step="any" class="form-control input-xs" placeholder="0.00"></div>
                 </div>
                 <div class="form-group">
                     <label class="col-xs-6 control-label label-xs">Libs. procesadas:</label>
                     <div class="col-xs-6"><input name="Lib_Proces" id="Lib_Proces" type="number" step="any" class="form-control input-xs" placeholder="0.00"></div>
                 </div>
             </div>
             <div class="col-xs-6">
                 <div class="form-group">
                     <label class="col-xs-6 control-label label-xs">Rendimiento %:</label>
                     <div class="col-xs-6">
                         <input name="Val_Rendi" id="Val_Rendi" type="number" step="any" class="form-control input-xs ">
                     </div>
                 </div>
                 <div class="form-group">
                     <label class="col-xs-6 control-label label-xs">Lote:</label>
                     <div class="col-xs-6"><input name="Val_Lote" id="Val_Lote" type="text" class="form-control input-xs " placeholder="0"></div>
                 </div>
                 <div class="form-group">
                     <label class="col-xs-6 control-label label-xs">Guia:</label>
                     <div class="col-xs-6"><input name="Val_Guia" id="Val_Guia" type="text" class="form-control input-xs " placeholder="0001"></div>
                 </div>
                 <div class="form-group">
                     <label class="col-xs-6 control-label label-xs">Gramaje global:</label>
                     <div class="col-xs-6"><input name="Val_Gram_Glo" id="Val_Gram_Glo" type="number" step="any" class="form-control input-xs " placeholder="0.00"></div>
                 </div>
                 <div class="form-group">
                     <label class="col-xs-6 control-label label-xs">Peso promedio:</label>
                     <div class="col-xs-6"><input name="Peso_Prom" id="Peso_Prom" type="number" step="any" class="form-control input-xs " placeholder="0.00"></div>
                 </div>
                 <div class="form-group">
                     <label class="col-xs-6 control-label label-xs">Piscina:</label>
                     <div class="col-xs-6"><input name="Val_Pisc" id="Val_Pisc" type="text" class="form-control input-xs " placeholder="0"></div>
                 </div>
                 <div class="form-group">
                     <label class="col-xs-6 control-label label-xs">Comisión:</label>
                     <div class="col-xs-6"><input type="number" name="Val_Comision" id="Val_Comision" step="any" class="form-control input-xs " placeholder="0.00"></div>
                 </div>
             </div>
             <div class="col-xs-12">
                 <div class="form-group">
                     <label class="col-xs-3 control-label label-xs">Controlador:</label>
                     <div class="col-xs-9"><input name="Vnd_Name" id="Vnd_Name" type="text" class="form-control input-xs " value="<?php echo ($vendedor["vendedores"]); ?>" readonly></div>
                     <input type="text" name="Vnd_Cod" id="Vnd_Cod" value="<?php echo ($vendedor["Vnd_Cod"]); ?>" hidden>
                 </div>
             </div>
         </fieldset>
     </div>
     <fieldset class="exa-fieldset">
         <legend class="Titulos2">Gastos:</legend>
         <div class="row">
             <div class="col-xs-6 col-sm-6 col-md-6">
                 <div class="form-group row">
                     <label class="col-xs-6 col-sm-6 col-md-6 col-form-label">Gasto Controlador:</label>
                     <div class="col-xs-6 col-sm-6 col-md-6">
                         <input type="number" class="form-control input-xs" step="any" id="Gast_Control" name="Gast_Control">
                     </div>
                 </div>
             </div>
             <div class="col-xs-6 col-sm-6 col-md-6">
                 <div class="form-group row">
                     <label class="col-xs-6 col-sm-6 col-md-6 col-form-label">Otros gastos:</label>
                     <div class="col-xs-6 col-sm-6 col-md-6">
                         <input type="number" class="form-control input-xs" step="any" id="Otr_Gastos" name="Otr_Gastos">
                     </div>
                 </div>
             </div>
         </div>
     </fieldset>
     <div class="form-group">
         <div class="center">
             <button type="button" class="btn btn-sm btn-danger no" onclick="cancelarLiqui();"><i class="glyphicon glyphicon-minus"></i> Cancelar</button>
             <button type="button" class="btn btn-sm btn-primary" onclick="$('#frm_Edit_Liq').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
         </div>
     </div>
 </form>