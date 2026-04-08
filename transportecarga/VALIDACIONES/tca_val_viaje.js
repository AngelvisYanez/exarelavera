/*
 * Copyright (c)2015 - EN Systems Apps
 * http://ensystems.ddns.net
 */
var clieDialog, searchGrid, searchform, formViaje;
$(function(){
    $('#edit').hide(); //$('#search').moveComp('#edit');
    searchform=$('#searchForm');
    formViaje=$('#formViaje');
    searchGrid=$('#searchGrid');
    if(searchGrid.length>0){
        searchGrid.createGrid({
            caption:'Viajes No Facturados', height: 250, rowNum:100000, datatype:'local',
            stateCol:'Via_Est', stateConfig:{I:'cellRed2',F:'cellGreen2'}, stateCondition:function(o){ return o.Via_Fac==='F'?'F':undefined; }, leyenda:[{icon:'stop green',label:'Viaje Facturado'},{icon:'remove red',label:'Anulado/Inactivo'}],
            orderOptions:[{label:'Por Fecha',value:'Via_Fec DESC'},{label:'Cliente',value:'Cliente'}],
            colModel: [
                { label: 'Cód. Int.', name: 'Via_Cod', width:20 ,align:"center", key:true, hidden:true}, $.originalRow(),
                { label: $.createIcon('check'), name: 'Select', width:15 ,align:"center", formatter:'checkboxExa', formatoptions:{conditional:function(o){ return o.Via_Est==='A'&&o.Via_Fac!=='F'; } } },
                { label: 'Fecha', name: 'Via_Fec', width:30 ,align:"center"},
                { label: 'Dia', name: 'Via_Dia', width:20 ,align:"center", formatter:'estado',formatoptions:{full:true,types:$.datepicker.regional['es'].dayNames}, classes:'columnHighlight1' },
                { label: 'Cliente', name: 'Cliente', width:50/*, formatter:'union', formatoptions:{sep:' - ',cols:['Ruc','Cliente']}*/ },
                { label: 'Vehiculo', name: 'Vehiculo', width:50, formatter:'union', formatoptions:{cols:['Veh_Pla','Veh_Mar','Veh_Col']} },
                { label: 'Chofer', name: 'Chofer', width:50 },
                { label: 'Carga', name: 'Car_Des', width:30 },
                { label: 'Origen', name: 'Ori_Aco', width:30, formatter:'union', formatoptions:{sep:' - ',cols:['Ori_Zon']} },
                { label: 'Destino', name: 'Des_Aco', width:30, formatter:'union', formatoptions:{sep:' - ',cols:['Des_Zon']} },
                { label: 'Km(s).', name: 'Via_Kil', width: 20, formatter:'number', summaryType:'sum', formatoptions:{decimalPlaces:0, defaultValue:''}, classes:'columnHighlight3'},
                { label: 'Cant.', name: 'Via_Can', width:25, formatter:'number' },
                { label: 'P.Unit.', name: 'Via_Pru', width:25, formatter:'number' },
                { label: 'TOTAL', name: 'Via_Tot', width:30, formatter:'function', formatoptions:{formatter:'currency', data:function(o){ return o.Via_Can*o.Via_Pru; } }, classes:'columnHighlight3' },
                { label: 'UTILIDAD', name: 'Via_Uti', width:30, formatter:'function', formatoptions:{formatter:'currency', data:function(o){ return o.Via_Can*o.Via_Pru-o.Via_Can*o.Via_Cpr; } }, classes:'columnHighlight4' },
                { label: 'Estado', name: 'Via_Est', width:30, hidden:true },
                { name:'update', label:$.createIcon('pencil'), width:20, align:'center',viewable: false, formatter:'gridButton', formatoptions:{ action:'editItem', icon:'pencil', title:'Editar Item', data:function(o){ return {Via_Cod:o.Via_Cod}; }, conditional:function(o){ return o.Via_Est==='A'&&o.Via_Fac!=='F'; } }, resizable: false },
                { name:'delete', label:$.createIcon('remove'), width:20, align:'center',viewable: false, formatter:'gridButton', formatoptions:{ action:'deleteItem', icon:'remove', title:'Eliminar Item', type:'danger', data:function(o){ return {Via_Cod:o.Via_Cod}; }, conditional:function(o){ return o.Via_Est==='A'&&o.Via_Fac!=='F'; }, caseFalse:function(o){ return o.Via_Est==='I'?$.createIcon('remove red'):''; } }, resizable: false }
            ],pgbuttons:false,pgtext:null
        },false,'#searchGridPager').gridButtonsAdd([null,
            {caption:'Agregar Viaje', buttonicon:'glyphicon glyphicon-plus', onClickButton:function(){ editItem({}); } },null,
            {caption:'Cambiar Cliente', buttonicon:'glyphicon glyphicon-plus', onClickButton:function(){ var sels=searchGrid.getSelectedByCol('Select','S'); if(!sels.length) return $.alert("Debe Seleccionar al Menos un <span class='green'>VIAJE</span>"); $('#viajesGrid').setRows(sels); $('#viajesDialog').dialog('open');  } }
        ]);
        $('.dateRangeInputs').createDateRange(30);
        var viajesCods=$('#viajesDialog');
        if(viajesCods.length===1){
            viajesCods.createSearchDialog({
                colModel:[
                    { label: 'C&oacute;d.Int.', name: 'Via_Cod', key: true, width: 15, align:"center",hidden:true },
                    { label: 'Fecha', name: 'Via_Fec', width: 25, align:"center" },
                    { label: 'Semana', name: 'OriginalData.Via_Sem', width:15 ,align:"center"},
                    { label: 'Dia', name: 'Via_Dia', width:20 ,align:"center", formatter:'estado',formatoptions:{full:true,types:$.datepicker.regional['es'].dayNames}, classes:'columnHighlight1' },
                    { label: 'Origen', name: 'Ori_Aco', width:30, formatter:'union', formatoptions:{sep:' - ',cols:['Ori_Zon']} },
                    { label: 'Destino', name: 'Des_Aco', width:30, formatter:'union', formatoptions:{sep:' - ',cols:['Des_Zon']} },
                    { label: 'Cant.', name: 'Via_Can', width:20, formatter:'number' },
                    { label: 'P.Unit.', name: 'Via_Pru', width:30, formatter:'number' }
                ]
            });
        }
         //Inicio del diálogo para clientes
        $('#clienteDialog').createSearchDialog([
            {label: 'Cód.Int.', name: 'Cli_Cod', key: true, hidden: true},
            {label: 'C&eacute;dula', name: 'Ruc', width: 30},
            {label: 'Cliente', name: 'Cliente', width: 70},
            {label: '&nbsp;', name: 'act1', width: 18, formatter:'gridButton', formatoptions:{action:'pasarDatos',data:['Cli_Cod','Ruc','Cliente','Prs_Dir',{dialog:'cliente',form:'#viajesForm'}]} }
        ],{title: 'Clientes'});
    }
    clieDialog=$('#clieDialog');
    if(clieDialog.length>0)
    clieDialog.createSearchDialog([ //buscar clientes
            { label: 'C&oacute;d.Int.', name: 'Prv_Cod', key: true, width: 15,align:"center",hidden:true },
            { label: 'Cédula/RUC', name: 'Prs_Ced', width: 50 },
            { label: 'Cliente', name: 'Cliente', width: 100},
            { label:'&nbsp;', name: 'act1', width: 20, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:'selectCliente'} }
        ], { title:'Proveedor' });

    if(formViaje.length>0){
        $('input.datepicker').createDatePickers();
        $(formViaje[0].Via_Fec).on('change', function(){
            if(this.value!==''){
                $('#Anio').html(this.value.substring(0,4));
                $(formViaje[0].Via_Sem).val(moment(this.value).isoWeek());
            }else{ $('#Anio').html('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'); $(formViaje[0].Via_Sem).val(''); }
        });
        $(formViaje[0].Via_Sem).fillSelect(function(){
            var s=[]; for(var i=1;i<=52;i++) s.push({value:i,label:i.ordinal(true)+'Semana'}); return s;
        }());
        $('.chosenDesc').createChosen({allow_single_deselect:true, template:function(t,d){ return '<div class="over"><b>'+t+'</b></div>'+(d['Vlu_Zon']!==''?'<div class="over desc"><b>ZONA: </b>'+d['Vlu_Zon']+'</div>':'');} });//.on('change',chosenChange);
        $('.chosen').createChosen();//.on('change',chosenChange);
        $('select.origen, select.destino').on('change', function(){
            if(!$('#Via_Cod').val().toNum() && !!$('#Ori_Cod').val().toNum() && !!$('#Des_Cod').val().toNum())
            $.get('',{kmAjax:true, Ori_Cod:$('#Ori_Cod').val(), Des_Cod:$('#Des_Cod').val()},function(r){
                $('#Via_Kil').val( r.kilometraje!==null? r.kilometraje['Via_Kil']: 0);
            }, 'json');
        });
        $('.TotalVals,.TotalCVals').on('change keyup', function(){
            var vals=$('#TotalGroup').getData();var valsc=$('#TotalComprasGroup').getData();
            $('#TotalGroup').setData({Via_Tot:$.toFixed(vals.Via_Can.toNum()*vals.Via_Pru.toNum())},'total',false);
            $('#TotalComprasGroup').setData({Via_Cto:$.toFixed(vals.Via_Can.toNum()*valsc.Via_Cpr.toNum())},'total',false);
            $('#Utilidad').setData({Uti_Tot:$.toFixed(vals.Via_Can.toNum()*vals.Via_Pru.toNum()-vals.Via_Can.toNum()*valsc.Via_Cpr.toNum())},'total');
        });
        $('#productoDialog').createSearchDialog([ //buscar productos
            {label: 'Cód.Int.', name: 'Pro_Cod', key: true, hidden: false, viewable: true, width: 15, align: 'center'},
            {label: 'Producto', name: 'Producto', width: 70},
            {label: 'Pld_Cod', name: 'Pld_Cod', hidden: true},
            {label: '&nbsp;', name: 'act1', width: 18, align: 'center', viewable: false, formatter:'gridButton', formatoptions:{action:'$("#cargaForm").setData($.cloneData($(this).data("originaldata")),"name",false);$("#productoDialog").dialog("close");'} }
        ], {title: 'Producto', options: [{label: '&nbsp;&nbsp;Producto&nbsp;&nbsp;', value: 'd'}, {label: '&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;', value: 'c'}]});
        $('#personaDialog').createSearchDialog([ //buscar personas
            {label: 'Cód.Int.', name: 'Prs_Cod', key: true, hidden: true},
            {label: 'C&eacute;dula', name: 'Prs_Ced', width: 30},
            {label: 'Persona', name: 'Persona', width: 70},
            {label: '&nbsp;', name: 'act1', width: 18, align: 'center', viewable: false, formatter:'gridButton', formatoptions:{action:'$("#choferForm").setData($.cloneData($(this).data("originaldata")),"name",false);$("#personaDialog").dialog("close");'} }
        ], {title: 'Personas', options: [{label: '&nbsp;&nbsp;Apellido&nbsp;&nbsp;', value: 'd'},{label: '&nbsp;&nbsp;C&eacute;dula&nbsp;&nbsp;', value: 'c'}]});
        $('#provDialog').createSearchDialog([ //buscar proveedores
            {label: 'Cód.Int.', name: 'Prv_Cod', key: true, hidden: true},
            {label: 'C&eacute;dula', name: 'Ruc', width: 30},
            {label: 'Persona', name: 'Proveedor', width: 70},
            {label: '&nbsp;', name: 'act1', width: 18, align: 'center', viewable: false, formatter:'gridButton', formatoptions:{action:'$("#vehiculoForm").setData($.cloneData($(this).data("originaldata")),"name",false);$("#provDialog").dialog("close");'} }
        ], {title: 'Personas', options: [{label: '&nbsp;&nbsp;Apellido&nbsp;&nbsp;', value: 'd'},{label: '&nbsp;&nbsp;C&eacute;dula&nbsp;&nbsp;', value: 'c'}]});
        //Se crea los diálogos
        function closeDialog(){ var f=this.find('form'); f.setData({},'name'); f.setData({}); }
        $('#cargaDialog').createDialog({height:170,width:400,icon:'glyphicon glyphicon-plus',afterClose:closeDialog});
        $('#modoDialog').createDialog({height:150,width:400,icon:'glyphicon glyphicon-plus',afterClose:closeDialog});
        $('#vehiculoDialog').createDialog({height:230,width:400,icon:'glyphicon glyphicon-plus',afterClose:closeDialog});
        $('#choferDialog').createDialog({height:235,width:475,icon:'glyphicon glyphicon-plus',afterClose:closeDialog});
        $('#lugarDialog').createDialog({height:170,width:400,icon:'glyphicon glyphicon-plus',afterClose:closeDialog});
    }
    if($('#reportForm').length>0){
        //Inicialización
        $('#tabs-1').data({type:'cliente',report:"Reporte por Cliente(s)",group:{
                groupField:['Ruc'],
                groupText:['<div><span style="float:left;"><b>{0}</b> - {Cliente}</span></div>','{0}'],
                groupColumnShow:[false], groupCollapse:false, groupSummary:[true], groupOrder:['asc']
            } });
        $('#tabs-2').data({type:'provee',report:"Reporte por Proveedor(es)",group:{
                groupField:['Ruc_Provee'],
                groupText:['<div><span style="float:left;"><b>{0}</b> - {Proveedor}</span></div>','{0}'],
                groupColumnShow:[false], groupCollapse:false, groupSummary:[true], groupOrder:['asc']
            } });
        $('#tabsMain').createTabs({beforeActivate:function(e,u){
                u.newPanel.find('>div:first-child>div:first-child').setData({},u.newPanel.data('type'));
                $("#reportDateRange").detach().appendTo(u.newPanel.find('>div:first-child'));
                $("#viajesReport").gridColUpdate('hide',['Via_Pru','Via_Tot']).gridColUpdate('show',['Via_Cpr','Via_Cto']);
                $("#viajesReport").clearGrid().setCaption(u.newPanel.data('report')).groupingGroupBy(u.newPanel.data('group').groupField[0],u.newPanel.data('group')).setGridParam({type:u.newPanel.data('type')});
            }});
        $('.datepicker').createDatePickers();
        $('.dateRangeInputs').createDateRange(7);
        //$('#Fec_Ini').val('');
        $('.semanaSearch').fillSelect(function(){ var s=[]; for(var i=1;i<=52;i++) s.push({value:i,label:i.ordinal(true)+" Semana"}); return s; }());
        //Inicio Grid para presentar el detalle de factura
        $("#viajesReport").createGrid({
            postData: $("#reportForm").getData("viajesAjax"), postExtra:{order:function(){ return $("#viajesReport").getGridParam('type')==='provee'?['Proveedor','Via_Fec']:['Cliente','Via_Fec']; }}, height: 250, caption:'Reporte por Cliente(s)', type:'cliente',
            totalCols:['Via_Kil','Via_Can','Via_Tot','Via_Cto'],totalDefault:{Car_Des:$.fieldSummarys()}, footerrow: true, userDataOnFooter: false, clearFootRow:true,
            stateCol:'Via_Fac', stateConfig:{'F':'cellGreen2'}, leyenda:[{icon:'stop green',label:'Viaje Facturado'}],
            colModel: [
                {label: 'Via_Cod', name: 'Via_Cod', key:true, hidden:true},
                {label: 'C&eacute;dula/R.U.C.', name: 'Ruc',width: 50, align: "center", excel:'text', hidden:true},
                {label: 'Cliente', name: 'Cliente',width: 50, align: "center", hidden:true, summaryType:$.fieldHeader, summaryTpl:' ' },
                {label: 'C&eacute;dula/R.U.C.', name: 'Ruc_Provee',width: 50, align: "center", excel:'text', hidden:true},
                {label: 'Proveedor', name: 'Proveedor',width: 50, align: "center", hidden:true, summaryType:$.fieldHeader, summaryTpl:' ' },
                {label: 'Fecha', name: 'Via_Fec', width: 30, align: "center"},
                {label: 'C.I.&nbsp;Chofer', name: 'Ruc_Chofer', width: 40, excel:'text' },
                {label: 'Chofer', name: 'Chofer', width: 50},
                {label: 'Telef.', name: 'Cho_Tel', width: 50, excel:'text', hidden:true},
                {label: 'Veh&iacute;culo', name: 'Veh_Pla', width: 30, align: "center",sorttype:'int'},
                {label: 'Origen', name: 'Ori_Aco', width:30, formatter:'union', formatoptions:{sep:' - ',cols:['Ori_Zon']} },
                {label: 'Destino', name: 'Des_Aco', width:30, formatter:'union', formatoptions:{sep:' - ',cols:['Des_Zon']} },
                {label: 'Booking', name: 'Via_Has', width: 25},
                {label: 'Conten.', name: 'Via_Con', width: 25},
                {label: 'Sello', name: 'Via_Sel', width: 25, hidden:true},
                {label: 'Cargamento', name: 'Car_Des', width: 25, align: "center", summaryType:'count',summaryTpl:$.fieldSummary()},
                {label: 'Km(s).', name: 'Via_Kil', width: 20, formatter:'number', summaryType:'sum', formatoptions:{decimalPlaces:0, defaultValue:''}, classes:'columnHighlight3'},
                {label: 'Cant.', name: 'Via_Can', width: 20, formatter:'number', summaryType:'sum'},
                {label: 'P.Com.', name: 'Via_Cpr', width: 20, formatter:'number', hidden:true},
                {label: 'TOTAL', name: 'Via_Cto', width: 20, summaryType:'sumCTot', classes:'bgNoColor', formatter:'function', formatoptions:{formatter:'number', noGroupFormat:true, data:function(o){ return o.Via_Can*o.Via_Cpr; } }, hidden:true },
                {label: 'P.Vent.', name: 'Via_Pru', width: 20, formatter:'number'},
                {label: 'TOTAL', name: 'Via_Tot', width: 20, summaryType:'sumTot', classes:'bgNoColor', formatter:'function', formatoptions:{formatter:'number', noGroupFormat:true, data:function(o){ return o.Via_Can*o.Via_Pru; } } },
                {label: 'Obs.', name: 'Via_Des', width: 15, align: "center", formatter:'truefalse', formatoptions:{ yesMsg:function(o){ return o.Via_Des; }, noMsg:' ', yesIcon:'info-sign', noIcon:' ', yesColor:'blue', noText:false }, title:false },
                {label: 'Estado', name: 'Via_Fac', width: 20, formatter:'estado', formatoptions:{full:true, types:{'F':function(o){return 'Fact. '+(o.Vet_Num||'');},'NF':'Sin&nbsp;Facturar'} } },
                {label: 'Lleva', name: 'Via_Lle', width: 25, hidden:true},
                {label: 'Guia', name: 'Via_Gui', width: 25, hidden:true},
                {label: 'Dia', name: 'Via_Dia', width:20 ,align:"center", hidden:true, formatter:'estado',formatoptions:{full:true,types:$.datepicker.regional['es'].dayNames}, classes:'columnHighlight1' },
                {label: 'Sem.', name: 'Via_Sem', width: 25, hidden:true},
                {label: 'Transito', name: 'Via_Tra', width: 25, hidden:true, formatter:'estado',formatoptions:{full:true, types:{N:'INDEFINIDO', E:'EXPORTACION', I:'IMPORTACION', L:'LOCAL', O:'OTROS'}} }
            ],
            grouping:true, groupingView :$('#tabs-1').data('group')
        }, true, "#viajesReportPager");
        //Inicio del diálogo para clientes
        $('#clienteDialog').createSearchDialog([
            {label: 'Cód.Int.', name: 'Cli_Cod', key: true, hidden: true},
            {label: 'C&eacute;dula', name: 'Ruc', width: 30},
            {label: 'Cliente', name: 'Cliente', width: 70},
            {label: '&nbsp;', name: 'act1', width: 18, formatter:'gridButton', formatoptions:{action:'pasarDatos',data:['Cli_Cod','Ruc','Cliente','Prs_Dir',{dialog:'cliente'}]} }
        ],{title: 'Clientes'});
        //Inicio del diálogo para proveedores
        $('#proveeDialog').createSearchDialog([
            {label: 'Cód.Int.', name: 'Prv_Cod', key: true, hidden: true},
            {label: 'C&eacute;dula', name: 'Ruc', width: 30},
            {label: 'Proveedor', name: 'Proveedor', width: 70},
            {label: '&nbsp;', name: 'act1', width: 18, formatter:'gridButton', formatoptions:{action:'pasarDatos',data:['Prv_Cod','Ruc','Proveedor','Prs_Dir',{dialog:'provee'}]} }
        ],{title: 'Proveedores'});
        //Inicio del diálogo para presentar choferes
        $('#choferDialog').createSearchDialog([
            {label: 'Cód.Int.', name: 'Cho_Cod', key: true, hidden: true},
            {label: 'C&eacute;dula', name: 'Ruc_Chofer', width: 30},
            {label: 'Chofer', name: 'Chofer', width: 70},
            {label: 'Ultimo Vehiculo', name: 'Vehiculo', width:30, formatter:'union', formatoptions:{cols:['Veh_Pla','Veh_Mar']} },
            {label: '&nbsp;', name: 'act1', width: 18, formatter:'gridButton', formatoptions:{action:'pasarDatos',data:['Cho_Cod','Ruc_Chofer','Chofer',{dialog:'chofer'}]} }
        ],{title: 'Choferes'} );
        $.getReportsExa("REPORTE DE VIAJES"); // crea los template para reportes
    }
});
function selectCliente(data){
    if(clieDialog.data('search')){
        searchform.data('cliente',data).setData(data,'cliente');
        searchGrid.Search('#searchForm','searchAjax');
    }else{
        formViaje.setData(data,'cliente');
    } clieDialog.dialog('close');
}
function editItem(data){
    formViaje.setData({});
    if($.isUnd(data['Via_Cod'])){
        formViaje.setData({Via_Fec:$.hoy()});
        formViaje.setData(searchform.data('cliente'),'cliente');
    }else{
        var dat=searchGrid.getCell(data.Via_Cod,'OriginalData');
        console.log(dat);
        formViaje.setData(dat).setData(dat,'cliente');
    } $('#search').moveComp('#edit');
}
function getFuncLabel(ai){ var a; try{ if(!$.isFunc(ai)) a=eval(ai); }catch(e){ a=ai; } return a; }
function formatChofer(data){ $.extend(data,{Ruc_Chofer:this.find('span.cedula').text(),Chofer:this.find('input.persona').val()}); return data; }
function formatVeh(data){ $.extend(data,{Vehiculo:`${data['Veh_Pla']} ${data['Veh_Mar']} ${data['Veh_Col']} `, Proveedor:this.find('span.proveedor').text()}); return data; }
//Función para guardar datos
function saveDatos(){
    var form=this, tipo=form.data('tipo'), data={'saveData':tipo,data:form.getData()};
    $.saveDataJson("",data,function(r){
        var sel=formViaje.find('select.'+tipo),
            isFunc=getFuncLabel(sel.attr('format')),
            dato=$.isFunc(isFunc)?isFunc.call(form,data['data']):data['data'];
        sel.fillSelectAdd(sel.attr('label'),'id',$.extend({id:r.id},dato),true,true);
        form.closest('.ui-dialog-content').dialog('close'); return false;
    });
}
function saveItem(){
    var data={saveForm:true,data:formViaje.getData()};
    $.createDialogConfirm('¿Esta seguro que desea <b class="green">GUARDAR</b> el <b class="blue">VIAJE/TRANSPORTE</b>?', data, function(){
        $.saveDataJson('',data,function(resp){
            if(data.Via_Cod!=='')searchGrid.gridUpdate();
            $('#edit').moveComp('#search').updateGridsSizes();
        });
    });
}
function deleteItem(data){
    data['deleteData']=true;
    $.createDialogConfirm('¿Esta seguro que desea <b class="red">ANULAR</b> el <b class="blue">VIAJE/TRANSPORTE</b>?<br/><span class="txtLeft"><b>NOTA:</b> Esta accion no se podra deshacer.</span>', data, function(){
        $.saveDataJson('',data,
            function (resp){ searchGrid.changeRow(data.Via_Cod,{Via_Est:'I',update:'','delete':''}); }
        );
    });
}

// para reportes
function sumTot(v,f,rc){ var vd=(($.isUnd(v)||v===0||v==="")&&!$.isUnd(rc.Via_Can)?rc.Via_Can*rc.Via_Pru:v||0); return vd; };
function sumCTot(v,f,rc){ var vd=(($.isUnd(v)||v===0||v==="")&&!$.isUnd(rc.Via_Can)?rc.Via_Can*rc.Via_Cpr:v||0); return vd; };
function pasarDatos(objeto){
    $('#'+objeto['dialog']+'Dialog').dialog('close');
    $(objeto['form']||'#reportForm').setData(objeto,objeto['dialog']);
}
/* imprimir/excel reporte*/
function exportarReporte(){
    var grid=$("#viajesReport"),type=grid.getGridParam('type');
    $.reportExa(grid, { excel:true, file:'Viajes',
        subTitle:grid.getCaption()+' Desde '+ $("#Fec_Ini").val() +' Hasta '+$("#Fec_Fin").val(),
        configs:$.extend({caption:true, unHideAll:true, removeCols:[0,3,4].concat(type!=='cliente'?[/*17,18,19,22*/20,21,24]:[1,2,/**/18,19]), colorGroup:true},type!=='cliente'?{footer:false,totalRows:false}:{})
    });
}
function imprimirReporte(){
    var grid=$("#viajesReport"),type=grid.getGridParam('type');
    $.reportExa(grid, {
        subTitle:grid.getCaption()+' Desde '+ $("#Fec_Ini").val() +' Hasta '+$("#Fec_Fin").val(),
        configs:$.extend({generated:false, removeCols:[6,7,9,13,14].concat(type!=='cliente'?[/*9,10,11*/]:[])},type!=='cliente'?{footer:false,totalRows:false}:{})
    });
}
function changeCliente(lista){
    var data=$.extend($('#viajesForm').getData('changeCliente'),{data:lista});
    $.saveDataJson('',data,function (resp){
        $('#viajesDialog').dialog('close');
        searchGrid.gridUpdate();
    });
}