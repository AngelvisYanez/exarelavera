<?php

/* 
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */
?>
<div id="documentoSearch">
    <form id="serachDocDorm" class="form-horizontal normal" action="javascript:$('#searchGrid').Search('#serachDocDorm','searchDocument');" >
        <div class="row">
            <input name="order" type="hidden" value="" />           

            <div class="col-xs-6">
                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">B�squeda</legend>
                    <div class="form-group">

                    <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>  
                    <div class="col-xs-10 radioset opt_search">
                          <input id="radsc1" name="op_opciones" type="radio" value="p" checked="" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc1">&nbsp;&nbsp;&nbsp;Cliente&nbsp;&nbsp;&nbsp;</label>
                          <input id="radsc2" name="op_opciones" type="radio" value="c" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc2">&nbsp;&nbsp;&nbsp;C&eacute;dula/RUC&nbsp;&nbsp;&nbsp;</label>
                          <input id="radsc3" name="op_opciones" type="radio" value="d" onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label for="radsc3">&nbsp;&nbsp;No. Documento&nbsp;&nbsp;</label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">B&uacute;squeda:</label>  
                    <div class="col-xs-7" >
                        <div class="input-group">                        
                        <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b�squeda..." autofocus  class="form-control input-sm clearable submit"/>
                        <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Documento"  tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                      </div><!-- /input-group --> 
                    </div><input type="text" tabindex="-1" style="display:none;" />                    
                </div>
                </fieldset>
            </div>
            <div class="col-xs-6">
                <fieldset class="exa-fieldset">
                    <legend class="Titulos2">Filtros</legend>
                    <div class="form-group">
                        <label class="col-xs-2 control-label label-xs">Documento:</label>  
                        <div class="col-xs-10" >    
                            <select name="Tic_Cod" class="form-control input-xs">
                                <option value=""><< TODOS >></option>
                               <?php foreach($rs_tip_compr as $row){ 
                                   if($row['Tic_Sri']!=5&&$row['Tic_Sri']!=7&&$row['Tic_Sri']!=23&&$row['Tic_Sri']!=24)
                                   echo "<option value='$row[Tic_Cod]' data-ticsri='$row[Tic_Sri]'>$row[Tic_Sri] - $row[Tic_Des]</option>";
                                } ?>
                            </select>
                        </div> 
                    </div> 
                    <div class="form-group">
                        <label class="col-xs-4 control-label label-xs">Periodo:</label>  
                        <div class="col-xs-3" >
                            <select name="Pec_Cod" class="form-control input-xs search_pec getData ins" onchange="if(this.value==='') $('#Cmb_Mes').attr('disabled','disabled'); else $('#Cmb_Mes').removeAttr('disabled');">
                                <option value=""><< TODOS >></option>
                                <?php  
                                    foreach ($rs_perio as $row){?>
                                    <option value="<?php echo $row['Pec_Cod'];?>" data-fecha_inicio="<?php echo $row['Pec_Fei'];?>" data-fecha_fin="<?php echo $row['Pec_Fef'];?>" ><?php echo $row['Year'];?></option>
                                <?php   }?>
                            </select>

                        </div> 
                        <label class="col-xs-2 control-label label-xs">Mes:</label>  
                        <div class="col-xs-3" >
                            <select id="Cmb_Mes" name="Cmb_Mes" class="form-control input-xs search_pec" disabled="disabled">
                               <option value=""><< TODOS >></option>
                               <?Php  for ($i=1;$i<=12;$i++){ ?><option <?php if ($i == $mes){ echo "selected=''"; } ?> value="<?Php echo $i; ?>"><?php echo mes($i, 1); ?></option><?Php } ?>
                            </select>
                        </div>                                    
                    </div> 
                </fieldset>
            </div>
        </div>    
    </form> 
    <div style="min-height: 300px;">
        <table id="searchGrid"></table>
        <table id="searchGridPager"></table>
        <div class="Titulos2"><span id="plan-footer"><strong>Leyenda:</strong> <span class="glyphicon glyphicon-stop green"></span> Contiene Pagos | <span class="glyphicon glyphicon-remove red"></span> Anulados/Inactivos | <span class="fa fa-globe green"></span> Retenci�n Electronica Validada | <span class="glyphicon glyphicon-lock orange"></span> Formato Anterior</div>
    </div>

</div>
<style>
    .control-label.detaMin{height: 17px;}
    .form-control.detaMin{font-size: 11px; padding: 1px 2px !important; line-height: 1.2 !important; height: 17px; margin: 0; }
</style>
<div id="docDetaDialog" title="Documento" style="display: none;">
    <fieldset class="exa-fieldset" >
        <legend class="Titulos2">Documento:</legend>
        <div class="form-horizontal normal" style="padding: 0 4px;">
        <div class="form-group">
            <label class="col-xs-2 control-label label-xs">C�dula/RUC:</label>  
            <div class="col-xs-4" ><span name="Prs_Ced"  class="form-control input-xs"></span></div>
            <label class="col-xs-2 control-label label-xs">Doc.Num.:</label>  
            <div class="col-xs-4" ><span name="Secuencia"  class="form-control input-xs"></span></div>
        </div>            
        <div class="form-group">
            <label class="col-xs-2 control-label label-xs">Cliente:</label>  
            <div class="col-xs-6" ><span name="Cliente"  class="form-control input-xs"></span></div>
            <label class="col-xs-1 control-label label-xs">Fecha:</label>  
            <div class="col-xs-3" ><span name="Caj_Fec"  class="form-control input-xs"></span></div>
        </div>    
        <div class="form-group">
            <label class="col-xs-2 control-label label-xs">Autorizaci�n:</label>  
            <div class="col-xs-10" ><span name="Autorizacion"  class="form-control input-xs datatitle"></span></div>
        </div>
        <div class="form-group condensed">
            <div class="col-xs-12"><div class="pull-right"><table id="detaDocu"></table></div></div>

        </div> 
        <div class="form-group">
            <label class="col-xs-2 col-xs-offset-7 control-label label-xs detaMin">Subtotal:</label>  
            <div class="col-xs-3" ><span name="Importe"  class="form-control input-xs isNumber detaMin"></span></div>
            <label class="col-xs-2 col-xs-offset-7 control-label label-xs detaMin">ICE:</label>  
            <div class="col-xs-3" ><span name="Ice_Tot"  class="form-control input-xs isNumber detaMin"></span></div>
            <label class="col-xs-2 col-xs-offset-7 control-label label-xs detaMin">IVA:</label>  
            <div class="col-xs-3" ><span name="Iva_Tot"  class="form-control input-xs isNumber detaMin"></span></div>
            <label class="col-xs-2 col-xs-offset-7 control-label label-xs detaMin">Descuento:</label>  
            <div class="col-xs-3" ><span name="Descu"  class="form-control input-xs isNumber detaMin"></span></div>
            <label class="col-xs-2 col-xs-offset-7 control-label label-xs detaMin">Total:</label>  
            <div class="col-xs-3" ><span name="Total"  class="form-control input-xs isMoney detaMin"></span></div>
        </div>  
        </div>    
    </fieldset>  
    <div class="col-xs-12" style="text-align: right;font-size: 8px;padding-top: 2px;"><b>CREACI�N:</b> <span name="Vet_Sys" class="databind"></span> &nbsp;&nbsp;-&nbsp;&nbsp; <b>USUARIO:</b> <span name="Vendedor" class="databind"></span></div>
</div>
<script>
    $(function() { 
        var model = [  
            { label: 'C�d. Int.', name: 'Vet_Cod', width: 30 ,align:"center", key:true},                  
            { label: 'Tipo Documento', name: 'Tic_Des', width: 100 },
            { label: 'No. Documento', name: 'Secuencia', width: 60, align:"center" }, 
			{ label: 'Referendo', name: 'Ref_Cod', width: 30, align:"center", formatter:'estado', formatoptions:{full:true,types:{'01':'SI','02':'NO'}} }, 
            { label: 'Export.', name: 'Exportacion', width: 20, align:"center", formatter:'truefalse', formatoptions:{yesMsg:'Tiene Exportacion',noMsg:' '}, title:false},
            { label: 'Fecha', name: 'Caj_Fec', width: 45, align:"center"},
            { label: 'RUC/Cedula', name: 'Ruc', width: 100, align:"center" },
            { label: 'Cliente', name: 'Cliente', width: 75},             
            { label: 'Estado', name: 'Vet_Est', width: 20,align:"center", formatter:'estado', title:false },                
            { label: '&nbsp;', name: 'act0', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{ action:viewInfo, data:'Vet_Cod', title:'Ver Documento', icon:'info-sign', type:'info' }, title:false }
        ];
        $('#searchGrid').createGrid({
            height: 270, datatype: "local", caption:'Resultados <div class="pull-right"><b>ORDENAR POR:</b>&nbsp;<select id="OrderBy"><option value="">No ordenar</option><option value="caja_aper.Caj_Fec DESC ">Fecha Venta</option><option value="ventas.Vet_Num DESC ">Num. Documento</option><option value="exporta_vent.Ref_Cod ">Referendo</option></select>&nbsp;</div>',
            colModel: model.concat($.isset('busquedaButton')?busquedaButton:[]) 
        },false,'#searchGridPager',{refresh: true});
        $('#OrderBy').on('change',function(){ $('input[name=order]').val($(this).val()); $('#serachDocDorm').formSubmit(); });
        
        var opts={                                                        
            height:75, postData: {CheListAjax:true},caption:'Detalle Venta',                
            colModel: [
                { label: 'C�d.Int.', name: 'Vet_Int', key: true, width: 15,align:"center", hidden:true },                                     
                { label: 'Cantidad ', name: 'Vet_Can', width: 45, align: 'right' },                      
                { label: 'Item', name: 'Ite_Lar', width: 130  },
                { label: 'P. Unit.', name: 'Vet_Pru', width: 65, align: 'right'},
                { label: 'Importe', name: 'Vet_Imp', width: 65, align: 'right', formatter:'currency', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryType: "sum"}
            ]       
        };
        $('#detaDocu').createGrid($.extend(opts,{height:'auto',width:550,responsive:false,caption:null,rownumbers:false}),true);
        $('#detaReten').createGrid($.extend(opts,{height:'auto',width:550,responsive:false,caption:null,rownumbers:false}),true); 
        $('#detaReten').getFootRow(true);
        $('#docDetaDialog').createDialog({height:400,width:600,noTitleStuff:false,noBorder:true});
    });
    function setOpt(val){ if(val==='d') $('.search_pec').attr('disabled','disabled'); else $('.search_pec').removeAttr('disabled'); }
    
    function viewInfo(doc){
        $('#RetenViewGrid')[$.varValid(doc['Com_Cod'])&&doc['Ret_Exi']==='S'?'show':'hide']();
        $.getDataJson('',{'docDetalle':true,'Vet_Cod':doc},function(resp){ 
            $('#docDetaDialog').setData(resp); 
            $('#detaDocu').setRows(resp['Vet_Items']);
            $('#docDetaDialog').dialog('open').updateGridsSizes();
        });
    }
</script>    
