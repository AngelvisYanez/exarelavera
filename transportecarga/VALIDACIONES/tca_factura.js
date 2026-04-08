$(function(){
    
    $.createDatePickers('.datepicker');
    
    //Inicio del di�logo para presentar clientes
    $.createSearchDialog('#clientefacturaDialog', [
        {label: 'C&oacute;d.Int.', name: 'Cli_Cod', key: true, hidden: true},
        {label: 'C&eacute;dula', name: 'Prs_Ced1', width: 30},
        {label: 'Clientes', name: 'cliente1', width: 70},
        {label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center', viewable: false,
            formatter: function (cellvalue, options, rowObject) {
                return $.getGridButton(cambiarCliente, rowObject);
            }
        }
    ], null, null, null, null, {title: 'Clientes', options: [{label: '&nbsp;&nbsp;Apellido&nbsp;&nbsp;', value: 'd'},
    {label: '&nbsp;&nbsp;C&eacute;dula&nbsp;&nbsp;', value: 'c'}]});
    
    //Inicio Grid para presentar el detalle de factura
    $('#Det_Fac').createGrid({
        caption:'DETALLE DE FACTURA', height:'auto',
        colModel:[
            {label:'C&oacute;digo',name:'Via_Cod',key:true,width:50,align:'center'},
            {label:'Pro_Cod.',name:'Pro_Cod',hidden:true},
            {label:'Iva_Cod.',name:'Iva_Cod',hidden:true},
            {label:'Ite_Lar',name:'Ite_Lar',hidden:true},
            {label:'Cant.',name:'Vet_Can',align:'center',width:50},
            {label:'Modo Trabajo',name:'Mot_Des',align:'center',width:50},
            {label:'Descripci&oacute;n',name:'Car_Des',align:'center',width:250},
            {label:'P.Unitario',name:'Vet_Pru',align:'right',width:50,title:false},
            {label:'Importe',name:'Vet_Imp',align:'right',width:50},
            {label:'I.V.A.',name:'Iva_Por',align:'center',width:50,title:false},
            {label:'I.C.E.',name:'Vet_Ice',align:'center',width:50},
            {label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center', viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    return $.getGridButton(quitarItem, rowObject,'Eliminar','glyphicon glyphicon-remove','','danger');
                }
            }
        ],pgbuttons:false,pgtext:null,footerrow:true
    },true);
    $('#Det_Fac').getFootRow(true);          
    $('#Det_Fac').jqGrid('footerData', 'set',{
            Vet_Pru:'<div class="footerFact"><label>SUBTOTAL:</label><label>TARIFA 0%:</label><label>TARIFA <span class="iva_por"></span>%:</label><label><span class="iva_por"></span>% IVA:</label><label>ICE:</label><label>DESCUENTO:</label><label class="total">TOTAL:</label></div>',
            Vet_Imp:'<div class="footerFact formDatos" id="formTotales"><form id="frm_dat" name="frm_dat"><input type="hidden" id="vet_des" name="vet_des"><input id="t_subtotal" name="t_subtotal" type="text" readonly/><input id="t_iva0" name="t_iva0" type="text" readonly/><input id="t_iva12" name="t_iva12" type="text" readonly/><input id="t_iva" name="t_iva" type="text" readonly/><input id="t_ice" name="t_ice" type="text" readonly/><input id="t_descuento" name="t_descuento" type="text" value="0"/><input id="Vet_Tot" name="Vet_Tot" type="text"  class="total" readonly/></form></div>',
            Iva_Por:'<div class="footerFact formDatos"><form id="frm_da2" name="frm_da2" action="javascript:"><div style="height:48px;"></div><div style="position:absolute;text-align: left;"><select id="Iva_Cod" name="Iva_Cod" class="calcular"></select></div><div style="height:38px;"></div><div style="position:absolute;text-align: left;"><select id="Des_Dpo" name="Des_Dpo" class="calcular"><option value="1">%</option><option value="2">$</option></select></div><div><input id="Vet_Des" name="Vet_Des" type="text" value="0" class="calcular"/></div><div style="height:10px;"></form></div>'
    },false);
    
    //Inicio Grid para presentar el detalle de factura
    $('#Imp_Asi').createGrid({
        caption:'COMPROBANTE <div id="jqGridButtonDiv"><button id="imprimir_com" type="button" onclick="$.imprimirUrl($(this).data(\'url_com\'));" class="btn btn-primary btn-xs" role="button"><span class="glyphicon glyphicon-print"></span> Imprimir</button></div>', height:'auto',
        colModel:[
            {label:'Pld_Cod',name:'Pld_Cod',hidden:true},
            {label:'C&oacute;digo',name:'Pld_Cdc',align:'center',width:60},
            {label:'Cuenta',name:'Pld_Des',align:'center',width:130},
            {label:'Glosa',name:'Asi_Con',align:'center',width:160},
            {label:'Asi_Deh',name:'Asi_Deh',hidden:true},
            {label:'Debe',name:'Asi_Deb',align:'center',width:50,sorttype:"float",formatter:'currency',formatoptions:{defaultValue:'-'},summaryType:'sum'},
            {label:'Haber',name:'Asi_Hab',align:'center',width:50,sorttype:"float",formatter:'currency',formatoptions:{defaultValue:'-'}, summaryType:'sum'}
        ],pgbuttons:false,pgtext:null,footerrow:true,userDataOnFooter: false,
        loadComplete: function () {
                var Sum_Deb = $('#Imp_Asi').jqGrid('getCol', 'Asi_Deb', false, 'sum');
                var Sum_Hab = $('#Imp_Asi').jqGrid('getCol', 'Asi_Hab', false, 'sum');
                $('#Imp_Asi').jqGrid('footerData','set',{Asi_Con: 'TOTALES:',Asi_Deb:Sum_Deb, Asi_Hab:Sum_Hab});
            }
    },true);
    
    //Inicio Grid para presentar el detalle de factura
    $('#Imp_Fac').createGrid({
        caption:'DETALLE DE FACTURA <div id="jqGridButtonDiv"><button id="imprimir_dfa" type="button" onclick="$.imprimirUrl($(this).data(\'url_dfa\'));" class="btn btn-primary btn-xs" role="button"><span class="glyphicon glyphicon-print"></span> Imprimir Detalle</button>&nbsp;<button id="imprimir_fac" type="button" onclick="$.imprimirUrl($(this).data(\'url_fac\'));" class="btn btn-primary btn-xs" role="button"><span class="glyphicon glyphicon-print"></span> Fact. Resumida</button>&nbsp;<button id="imprimir_gru" type="button" onclick="$.imprimirUrl($(this).data(\'url_gru\'));" class="btn btn-primary btn-xs" role="button"><span class="glyphicon glyphicon-print"></span> Fact. Detallada</button></div>', height:'auto',
        colModel:[
            {label:'Cant.',name:'Vet_Can',align:'center',width:50},
            {label:'Descripci&oacute;n',name:'Car_Des',align:'center',width:240},
            {label:'P.Unitario',name:'Vet_Pru',align:'right',width:60,title:false},
            {label:'Importe',name:'Vet_Imp',align:'right',width:50},
            {label:'I.V.A.',name:'Iva_Por',align:'center',width:50,title:false},
            {label:'I.C.E.',name:'Vet_Ice',align:'center',width:50}
        ],pgbuttons:false,pgtext:null,footerrow:false
    },true);
    
    //Change para el cambio de periodo
    $("#Pec_Cod").change(function(){
        $('#Fac_Fec').dateLimits($('#Pec_Cod').find('option:selected').data('inicio'),$('#Pec_Cod').find('option:selected').data('fin'));
    });

    //Change para obtener el n�mero de secuencia
    $("#Tic_Cod").change(function(){
        numSecuencia(this.value);
    });

    //Change para cambiar la forma de pago
    $('#For_Cod').change(function(){
        (this.value==='2')?($("[id^='Cre_']").show(),$("[id^='Con_']").hide(),cargarCtad($('#Pec_Cod').val())):($("[id^='Cre_']").hide(),$('#Con_Cue').show());
        cargar_tipoPago(this.value);
    });
    
    //Capturar evento cuando termina de escribir en el input Vet_Num
    $("#Vet_Num").change(function(){
        if(this.value*1>aut_fin){
            $.alert('N&uacute;mero de secuencia '+this.value+' no puede ser mayor que el permitido '+aut_fin);
            $("#Vet_Num").val(num_fac);
        }else{
            if(this.value*1<aut_ini){
                $.alert('N&uacute;mero de secuencia '+this.value+' no puede ser menor que el permitido '+aut_ini);
                $("#Vet_Num").val(num_fac);
            }else{
                verificarNrosecuencia(this.value);
            }
        }
    });
    
    //Change para obtener los totales de la factura
    $('.calcular').change(function(){
        calcular();
    });
});

    /*Funci�n para calcular los valores de : subtotal, tarifa0, tarifa12, iva, ice, descuento y total de una factura de venta*/
    /*PAR�METROS QUE RECIBE*/
    /*  arreglo:detalle de la factura el cual puede ser un array 
        tipo:   indica si el tipo de descuento se lo efect�a en d�lares o en porcentaje
        desc:   valor del descuento, el mismo que generalmente es en porcentaje y de esta manera se guarda en la BD
        iva:    valor del iva 12,14 o 0
        ivacod: Iva_Cod del iva que se ha seleccionado
     */
    function totales(arreglo,tipo,desc,iva,ivacod){
        var subtotal=0,ice=0,tarifa0=0,tarifa12=0,descuento=0,descuento1=0,importe_descuento=0,totaliva=0,valor=0,total=0,ice_individual=0,iva_individual=0;

        $.each(arreglo,function (i,v){
            subtotal=subtotal*1+v['Vet_Imp']*1;
        });

        if(tipo*1===1){
            valor=(subtotal*desc)/100;
            descuento=valor;
        }else{
            valor=(100*desc)/subtotal;
            descuento=desc;desc=valor;
        }

        $.each(arreglo,function (i,v){
            if(v['Iva_Por']*1>0){
                descuento1=(v['Vet_Imp']*desc)/100;
                importe_descuento=v['Vet_Imp']-descuento1;
                ice_individual=(importe_descuento*v['Pro_Ice'])/100;
                ice=ice*1+ice_individual*1;
                iva_individual=((importe_descuento+ice_individual)*iva)/100;
                totaliva=totaliva*1+iva_individual*1;
                tarifa12=tarifa12*1+v['Vet_Imp']*1;
                $.extend(arreglo[i],{Iva_Por:iva,Iva_Cod:ivacod});
            }else{
                tarifa0=tarifa0*1+v['Vet_Imp']*1;
            }
        });

        descuento=$.toFixed(descuento);
        totaliva=$.round(totaliva);ice=$.round(ice);
        total=(tarifa0+tarifa12+totaliva+ice)-descuento;
        return ({Det_Fac:arreglo,t_subtotal:$.toFixed(subtotal),t_iva0:$.toFixed(tarifa0),t_iva12:$.toFixed(tarifa12),t_iva:$.toFixed(totaliva),t_ice:$.toFixed(ice),t_descuento:descuento,Vet_Tot:$.toFixed(total),vet_des:desc});
    }

    /* Funci�n para calcular el siguiente n�mero de secuencia*/
    /*PAR�METROS QUE RECIBE*/
    /*
        arreglo:    El mismo que posee todos los datos concernientes a la tabla autorizaci    
    */
    function numsecuencia(arreglo){
        var vet_num='',aut_sri='',aut_cod='',aut_fin='',aut_ini='',aut_cad='',aut_fci='';
        if(!$.varValid(arreglo['Aut_Cod'])){
            $.alert('No se ha detectado autorizaci&oacute;n para este tipo de comprobante..!!');
        }else{
            !$.varValid(arreglo['Vet_Cod'])?vet_num=arreglo['Aut_Ini']:vet_num=arreglo['Num_Sig'];
            aut_fin=arreglo['Aut_Fin'];aut_ini=arreglo['Aut_Ini'];
            if(vet_num>(aut_fin*1)){
                $.alert('El n&uacute;mero de secuencia '+vet_num+' a excedido el permitido '+aut_fin+' por el n&uacute;mero de autorizaci&oacute;n #'+arreglo['Aut_Sri']);
            }else{
                if(arreglo['Fec_Sys']>arreglo['Aut_Cad']){
                    $.alert('La fecha m&aacute;xima para el uso de la autorizaci&oacute;n n&uacute;mero '+arreglo['Aut_Sri']+' es '+arreglo['Aut_Cad']);
                }else{
                    aut_sri=arreglo['Aut_Sri'];aut_cod=arreglo['Aut_Cod'];aut_cad=arreglo['Aut_Cad'];aut_fci=arreglo['Aut_Fci'];
                }
            }
        }
        return ({Vet_Num:vet_num,Aut_Sri:aut_sri,Aut_Cod:aut_cod,Aut_Ini:aut_ini,Aut_Fin:aut_fin,Aut_Cad:aut_cad,Aut_Fci:aut_fci});
    }

    //Funci�n para cargar los tipos de pago
    function cargar_tipoPago(for_cod){
        $.post("",{cargar_tipoP:true,For_Cod:for_cod},function(response){
            $('#frm_fpa').setData(response,false);cargarBancos($('#Pag_Cod').val());
        },'json').fail(function(){$.alert();});
    }

    //Funci�n para cargar los bancos del plan de cuentas
    function cargarBancos(pag_cod){
        $.post("",{cargarBancos:true,Pag_Cod:pag_cod},function(response){
            $('#frm_fpa').setData(response,false);
        },'json').fail(function(){$.alert();});
    }

    //Funci�n para cargar las cuentas deudoras
    function cargarCtad(pec_cod){
        $.post("",{cargarCtad:true,Pec_Cod:pec_cod},function(response){
            $('#frm_fpa').setData(response,false);
        },'json').fail(function(){$.alert();});
    }

    //Funci�n para cargar los datos de un cliente seleccionado
    arreglo=[];
    function cargarCliente(cliente) {
        $('#clienteDialog').dialog('close');
        $('input[name=Cli_Cod]').val(cliente.Cli_Cod);
        $('#frm_cab').setData(cliente,false);
        $.post("",{cargarViajes:true,Cli_Cod:cliente.Cli_Cod},function(response){
            arreglo=response;
            calcular();
        },'json').fail(function(){$.alert();});
    }
    
    //Funci�n para cambiar el cliente al momento de realizar la factura
    function cambiarCliente(cliente) {
        $('#clientefacturaDialog').dialog('close');
        $('input[name=Cli_Cod]').val(cliente.Cli_Cod);
        $('#frm_cab').setData(cliente,false);
    }

    //Funci�n para quitar un elemento del detalle de factura
    function quitarItem(item){
        $.createDialogConfirm('Desea Eliminar el item seleccionado..!!',null,function(){
            $.arraySpliceWhere(arreglo,'Via_Cod',item['Via_Cod'],false);
            calcular();
            if(arreglo.length<1){$("[id^='t_']").val(0);}
        });
    }

    //Funci�n para efectuar los calculos de los totales de la factura
    function calcular(){
        var total=totales(arreglo,$('#Des_Dpo').val(),$('#Vet_Des').val(),$('#Iva_Cod').find('option:selected').data('iva'),$('#Iva_Cod').val());
        $('#frm_dat').setData(total);$('.iva_por').text($( "#Iva_Cod option:selected" ).text());
        $("#Det_Fac").setRowsByIndex(total['Det_Fac']);
        $("#Det_Fac").jqGrid('resizeGrid');
        if(total['Vet_Tot']*1>1000){$('#tpa_com').show();}
    }

    //Funci�n para obtener el n�mero de secuencia
    var num_fac=0,aut_fin=0,aut_ini=0,aut_cad='',aut_fci='';
    function numSecuencia(tic_cod){
        $.post("",{Tic_Cod:tic_cod,numeroSec:true},function(response){
            var datos_cab=numsecuencia(response);
            $('#frm_cab').setData(datos_cab,false);
            num_fac=datos_cab['Vet_Num'];
            aut_ini=datos_cab['Aut_Ini'];
            aut_fin=datos_cab['Aut_Fin'];
            aut_cad=datos_cab['Aut_Cad'];
            aut_fci=datos_cab['Aut_Fci'];
        },'json').fail(function(){$.alert();});
    }
    
    //Funci�n para comprobar si el n�mero de secuencia ya se encuentra registrado
    function verificarNrosecuencia(vet_num){
        var data={verificarNrosecuencia:true,Vet_Num:vet_num,Tic_Cod:$('#Tic_Cod').val()};
        $.post("",data,function(response){
            if(response['existe']===true){
                $.alert('N&uacute;mero de secuencia '+vet_num+' ya se encuentra registrado..!!');
                $("#Vet_Num").val(num_fac);
            }
        },'json').fail(function(){$.alert();});
    }
    
    //Funci�n para presentar cuadro de asientos contables
    function asientos(Com_Cod,Vet_Cod){
        $.post("",{cargarAsiento:true,Com_Cod:Com_Cod,Vet_Cod:Vet_Cod},function(response){
            $('#Imp_Asi').setRowsByIndex(response['det_asi']);
            $('#Imp_Fac').setRowsByIndex(response['det_fac']);
            $('#frm_cco').setData(response['cab_com']);
            $('#frm_cfa').setData(response['cab_fac']);
        },'json').fail(function(){$.alert();});
    }