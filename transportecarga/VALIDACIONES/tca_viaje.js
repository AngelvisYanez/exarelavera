$(function(){
    
    $("#Ciu_Cod").createChosen('input-xs');
    
    $('#Prs_Ced').change(function(){
        buscarCliente(this.value);
    });
    
    //Se crea los diálogos
    $('#cargamentoDialog').createDialog({height:170,width:400,icon:'glyphicon glyphicon-plus'});
    $('#modoDialog').createDialog({height:150,width:400,icon:'glyphicon glyphicon-plus'});
    $('#automotorDialog').createDialog({height:190,width:400,icon:'glyphicon glyphicon-plus'});
    $('#choferDialog').createDialog({height:270,width:475,icon:'glyphicon glyphicon-plus'});
    
    //Inicio del diálogo para presentar clientes
    $.createSearchDialog('#clienteDialog', [
        {label: 'Cód.Int.', name: 'Cli_Cod', key: true, hidden: true},
        {label: 'C&eacute;dula', name: 'Prs_Ced', width: 30},
        {label: 'Cliente(s)', name: 'cliente', width: 70},
        {label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center', viewable: false,
            formatter: function (cellvalue, options, rowObject) {
                $.extend(rowObject,{dialog:'cliente',frm:'frm_via'});return $.getGridButton(pasarDatos, rowObject);
            }
        }
    ], null, null, null, null, {title: 'Clientes', options: [{label: '&nbsp;&nbsp;Apellido&nbsp;&nbsp;', value: 'd'},
    {label: '&nbsp;&nbsp;C&eacute;dula&nbsp;&nbsp;', value: 'c'}]});    
    
    //Inicio Grid para presentar plantilla para reguistrar viajes de un cliente determinado
    $('#Via_Grid').createGrid({
        caption:'REGISTRO DE VIAJES', height:'350',
        colModel:[
            { label: 'Cod', name: 'Via_Cod',key:true,hidden:true },
            { label: 'Cho_Cod', name: 'Cho_Cod',hidden:true,formatter:'input2',formatoptions:{id:'2',attr:''}},
            { label: '<span class="required"></span> Conductor', name: 'Con_Duc', width: 80,align:'center',title:true,formatter:'input2',formatoptions:{class:'chofer',title:'Agregar Conductor',id:'1',action:"$('#choferDialog').dialog('open');$('#frm_cho')[0].reset();"}},
            { label: 'Veh_Cod', name: 'Veh_Cod',hidden:true,formatter:'input2',formatoptions:{id:'2',attr:''}},
            { label: '<span class="required"></span> Automotor', name: 'Aut_Mot', width: 40,align:'center',title:false,formatter:'input2',formatoptions:{class:'vehiculo',title:'Agregar Veh&iacute;culo',id:'1',action:"$('#automotorDialog').dialog('open');$('#frm_aut')[0].reset();"}},
            { label: '<span class="required"></span> Cargamento', name: 'Car_Cod', width: 65,align:'center',title:false,formatter:'select1', formatoptions:{class:'select_carga',id:'car_cod',title:'Agregar Cargamento',action:"$('#cargamentoDialog').dialog('open');"} },
            { label: '<span class="required"></span> Modo Trabajo', name: 'Mot_Cod', width: 55,align:'center',title:false,formatter:'select1', formatoptions:{class:'select_modo',id:'mot_cod',title:'Agregar Modo Trabajo',action:"$('#modoDialog').dialog('open');"}},
            { label: '<span class="required"></span> Fecha', name: 'Via_Fec', width: 40,align:'center',title:false,formatter:'input2',formatoptions:{id:'2',attr:''}},
            { label: '<span class="required"></span> Origen', name: 'Via_Ded', width: 80,align:'center',title:false,formatter:'input2',formatoptions:{id:'2',attr:''}},
            { label: '<span class="required"></span> Destino', name: 'Via_Has', width: 80,align:'center',title:false,formatter:'input2',formatoptions:{id:'2',attr:''}},
            { label: '<span class="required"></span> Cant.', name: 'Via_Can', width: 30,align:'center',title:false,editable:true, editoptions:{dataInit:styleCant}},
            { label: '<span class="required"></span> P.U.', name: 'Via_Pru', width: 30,align:'center',title:false,editable:true, editoptions:{dataInit:stylePru}},
            { label: 'Total', name: 'Via_Tot', width: 30,title:false,formatter:'input2',formatoptions:{id:'2',attr:'readonly'}},
            { label: 'Via_Est', name: 'Via_Est',hidden:true,formatter:'input2',formatoptions:{id:'2',attr:''}},
            { label: 'Via_Aux', name: 'Via_Aux',hidden:true,formatter:'input2',formatoptions:{id:'2',attr:''}},
            {label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center', viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    return $.getGridButton(quitarViaje, rowObject,'Eliminar','glyphicon glyphicon-remove','','danger');
                }
            }
        ],pgbuttons:false,pgtext:null,footerrow:false,beforeSelectRow: function(rowid, e) {return false;}
    },true,'#Via_Page',{view:false,refresh:false}).gridButtonAdd({
                caption:"Agregar campo",
                id:'btn_agr',
                buttonicon:"glyphicon glyphicon-plus", 
                title:'Agregar',
                onClickButton: function (){agregarFila(0);}                
            });
    
    $.fn.fmatter.select1=function(cv,opts,cObjt){ 
        var set=opts['colModel']['formatoptions'],op=$('#'+set['id']).html(),el=$('<div class="input-group input-group-xs ret"><select id="'+opts['rowId']+'_'+opts['colModel']['name']+'" name="'+opts['colModel']['name']+'" class="form-control input-xs '+set['class']+'">'+op+'</select><span class="input-group-btn"><button class="btn btn-info" type="button" title="'+set['title']+'" onclick="'+set['action']+'"><span class="glyphicon glyphicon-plus"></span></button></span></div>');
        return el.prop('outerHTML');
    };
    $.fn.fmatter.select1.unformat=function(cv,opts,cObjt){ return $(cObjt).find(':input').val(); };
    
    $.fn.fmatter.input2=function(cv,opts,cObjt){ 
        var set=opts['colModel']['formatoptions'],el;
        if(set['id']==='1'){el=$('<div class="input-group input-group-xs ret"><input type="text" id="'+opts['rowId']+'_'+opts['colModel']['name']+'" name="'+opts['colModel']['name']+'" class="form-control input-xs '+set['class']+'"/><span class="input-group-btn"><button class="btn btn-info" type="button" title="'+set['title']+'" onclick="'+set['action']+'"><span class="glyphicon glyphicon-plus"></span></button></span></div>');}
        else{el=$('<input type="text" id="'+opts['rowId']+'_'+opts['colModel']['name']+'" name="'+opts['colModel']['name']+'" class="form-control input-xs" '+set['attr']+'/>');}
        return el.prop('outerHTML');
    };
    $.fn.fmatter.input2.unformat=function(cv,opts,cObjt){ return $(cObjt).find(':input').val(); };
    
    //Inicio del diálogo para presentar productos 
    $.createSearchDialog('#productoDialog', [
        {label: 'Cód.Int.', name: 'Pro_Cod', key: true, hidden: false, viewable: true, width: 15, align: 'center'},
        {label: 'Producto', name: 'Ite_Lar', width: 70},
        {label: 'Pld_Cod', name: 'Pld_Cod', hidden: true},
        {label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center', viewable: false,formatter: boton}
    ], null, null, null, null, {title: 'Producto', options: [{label: '&nbsp;&nbsp;Producto&nbsp;&nbsp;', value: 'd'},
    {label: '&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;', value: 'c'}]});
    function boton(cellvalue, options, rowObject){
        if($.varValid(rowObject.Pld_Cod)){
            $.extend(rowObject,{dialog:'producto',frm:'frm_car'});return $.getGridButton(pasarDatos, rowObject);
        }else{
            return $.getGridButton('','','Producto NO esta parametrizado','glyphicon glyphicon-lock','','btn btn-warning');
        }
    }

    //Inicio del diálogo para presentar personas
    $.createSearchDialog('#personaDialog', [
        {label: 'Cód.Int.', name: 'Prs_Cod', key: true, hidden: true},
        {label: 'C&eacute;dula', name: 'Prs_Ced', width: 30},
        {label: 'Persona', name: 'persona', width: 70},
        {label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center', viewable: false,
            formatter: function (cellvalue, options, rowObject) {
                return $.getGridButton(datosChofer, rowObject);
            }
        }
    ], null, null, null, null, {title: 'Personas', options: [{label: '&nbsp;&nbsp;Apellido&nbsp;&nbsp;', value: 'd'},
    {label: '&nbsp;&nbsp;C&eacute;dula&nbsp;&nbsp;', value: 'c'}]});
    
    //Inicio de diálogo para presentar los viajes de un determinado cliente
    $.createSearchDialog('#viajeDialog', [
        {label: 'Cód.Int.', name: 'Prs_Cod', key: true, hidden: true},
        {label: 'C&eacute;dula', name: 'Prs_Ced', width: 30},
        {label: 'Cliente(s)', name: 'cliente', width: 70},
        {label: 'Nro. Viajes', name: 'viajes', width: 70,align:'center'},
        {label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center', viewable: false,
            formatter: function (cellvalue, options, rowObject) {
                return $.getGridButton(datosViaje, rowObject);
            }
        }
    ], null, null, null, null, {title: 'Viajes por clientes', options: [{label: '&nbsp;&nbsp;Apellido&nbsp;&nbsp;', value: 'd'},
    {label: '&nbsp;&nbsp;C&eacute;dula&nbsp;&nbsp;', value: 'c'}]});
    
});

    /*** FUNCIONES PARA EL MANEJO DE DATOS ***/
    
    //Función que agrega una fila al grid=>Via_Grid       
    function agregarFila(aux){
        $('#Via_Grid').jqGrid('resizeGrid');
        var $this=$('#Via_Grid'),id,nuevo;
        if(aux<1){id=($this.jqGrid('getCol','Via_Cod',false,'max')+1)||0;nuevo='N';}else{id=aux;nuevo='A';}
        
        $this.jqGrid('addRowData',id,{'Via_Cod':id});  
        $this.jqGrid('editRow',id);
        $('#'+id+'_Via_Aux').val(nuevo);
        
        $('#'+id+'_Aut_Mot').focus(function(){
            crear(id,this.name);
        });
        
        $('#'+id+'_Con_Duc').focus(function(){
            crear(id,this.name);
        });

        $.createDatePickers('#'+id+'_Via_Fec');  
    }
    
    function crear(id,name){
        $('#'+id+'_'+name).autocomplete({
            source:(name==='Aut_Mot')?arr_aut:arr_cho,
            response: function(event, ui) {
                if (!ui.content.length) {
                    if(name==='Aut_Mot'){
                        var noResult = { label:"Vehículo NO Registrado" };
                    }else{
                        var noResult = { label:"Conductor NO Registrado" };
                    }
                    ui.content.push(noResult);
                } 
            },
            select: function( event, ui ) {
                if(name==='Aut_Mot'){
                    $('#'+id+'_Veh_Cod').val(ui.item.Veh_Cod);
                }else{
                    $('#'+id+'_Cho_Cod').val(ui.item.Cho_Cod);
                    $('#'+id+'_Con_Duc').val(ui.item.label);
                    $('#'+id+'_Aut_Mot').val(ui.item.Veh_Pla);
                    $('#'+id+'_Veh_Cod').val(ui.item.Veh_Cod);
                }
            }
        });
    }
    
    //Función para eliminar un registro del grid
    function quitarViaje(viaje){
        $.createDialogConfirm('Desea Eliminar el item seleccionado..!!',null,function(){
            var aux=$('#'+viaje.Via_Cod+'_Via_Aux').val();
            if(aux==='N'){
                $("#Via_Grid").jqGrid('delRowData',viaje.Via_Cod);
            }else{
                $('#'+viaje.Via_Cod+'_Via_Est').val('I');
                $('#'+viaje.Via_Cod).hide();
            }
        });
    }
            
    //Estilo cantidad
    function styleCant(e,obj,opt){            
        e.style.textAlign = 'right';  e.placeholder='0'; 
        $(e).on('keyup',function (){
            if(isNaN(this.value)){ $(this).val('1').focus();   }
            else if (this.value % 1 !== 0){ var dec=String(this.value).split("."); if(typeof dec[1]!=='undefined'&&dec[1].length>2) this.value=$.toFixed(this.value);  } 
            updateRowItem(obj);
        });
    }

    //Estilo precio unitario
    function stylePru(e,obj,opt){            
        e.style.textAlign = 'right'; e.placeholder='0.00';
        $(e).on('keyup',function (){
           if(isNaN(this.value)){ $(this).val('').focus();; }
           else if (this.value % 1 !== 0){ var dec=String(this.value).split("."); if(typeof dec[1]!=='undefined'&&dec[1].length>8) this.value=$.toFixed(this.value,8);  } 
           updateRowItem(obj);
        });            
    }
            
    // Actualiza los valores de la fila
    function updateRowItem(obj){
        var row=$.extend({},$('#Via_Grid').jqGrid('getRowData',obj['rowId']),$('#Via_Grid').find('tr#'+obj['rowId']).getDataForced());
        row['Via_Tot']=row['Via_Can']*(0+row['Via_Pru'])*1;
        $("#Via_Grid").jqGrid("setCell", obj['rowId'], "Via_Tot", row['Via_Tot']);
        $('#'+obj['rowId']+'_Via_Tot').val($.toFixed(row['Via_Tot']));
    }
    
    //NOTE: Backspace = 8, Enter = 13, '0' = 48, '9' = 57, '.' = 46
    function onKeyDecimal(e) {
        var key = window.Event ? e.which : e.keyCode;
        return (key <= 13 || (key >= 48 && key <= 57) || key === 46);
    }
    
    // valida cedula
    function validaNoIdentif(number){
        var digitos = number.split(""), dto=digitos.length, acu=0, resp={success:false,message:''}, 
        coef={'NA':[2,1,2,1,2,1,2,1,2],'PU':[3,2,7,6,5,4,3,2,0],'PR':[4,3,2,7,6,5,4,3,2]}, modulo, acum=0;
        if(dto===0) resp['message']='No has ingresado ning\u00fan dato!'; 
        else{
         for(var i=0; i<dto; i++) if(!isNaN(digitos[i])){ digitos[i]=digitos[i]*1; acu = acu+1; }
         if(acu===dto){
          var tipo = digitos[2];
          if (tipo===7||tipo===8) resp['message']='"El tercer d\u00edgito ingresado es inv\u00e1lido"'; else{ tipo=(tipo<6?'NA':(tipo===6?'PU':(tipo===9?'PR':''))); modulo=(tipo==='NA'?10:11); resp['tipo_abrev']=tipo; resp['tipo']=(tipo==='NA'?'Natural':(tipo==='PR'?'Privada':(tipo==='PU'?'P\u00fablica':''))); }
              if(dto!==10&&dto!==13){ resp['message']='La cantidad de d\u00EDgitos deben ser 10 o 13'; return resp; }else{ resp['doc_abr']=(dto===10?'C':(dto===13?'R':'')); resp['doc']=(dto===10?'C\u00E9dula':(dto===13?'R.U.C.':'')); }   
              if(number.substring(0,2)*1>24) resp['message']='Los dos primeros d\u00EDgitos no pueden ser mayores a 24.';	
              if(dto===13){
                      if(number.substring(10,13)!=='001') resp['message']='Los tres \u00faltimos d\u00EDgitos no tienen el c\u00F3digo del RUC 001.'; 	
                      if(tipo==='PU'&&number.substring(9,13)!=='0001') resp['message']='El R.U.C. de la empresa del sector p\u00fablico debe terminar con 0001';
              }else if((tipo==='PU'||tipo==='PR')) resp['message']='El R.U.C. de las empresas '+resp['tipo']+'s deben tener 13 digitos!';
              if(resp['message'].length>0) return resp; 

              for(var a=0;a<9;a++){
                      var resul=digitos[a]*coef[tipo][a];
                      acum+=(resul-(tipo==='NA'&&resul>=10?9:0));
              }	
              var residuo=acum%modulo, digitoVerificador = residuo===0 ? 0: modulo - residuo;
              if(digitos[(tipo==='PU'?8:9)]!==digitoVerificador) resp['message'] = 'El n\u00famero de '+resp['doc']+' de la '+(tipo==='NA'?'Persona Natural':'Empresa '+resp['tipo'])+' ingresado es inv\u00E1lido!';

              if(resp['message'].length===0) resp['success']=true;
         }else resp['message']="ERROR: Solo debe contener d\u00EDgitos!";
        }
        return resp;
    }
    
    //Función que recibe el número de cédula 
    function buscarCliente(cliente){
        var respuesta=validaNoIdentif(cliente);
        if(respuesta['success']===true){
            $.post("",{buscarCliente:true,Prs_Ced:cliente},function(response){
                (response['existe']===true)?(datosChofer(response)):$.alert('La persona no se encuentra registrada..!!');
            },'json').fail(function(){$.alert();});
        }else{$.alert(respuesta['message']);}
    }
    
    //Función para pasar datos seleccionados
    function pasarDatos(objeto){
        $('#'+objeto['dialog']+'Dialog').dialog('close');
        $('#'+objeto['frm']).setData(objeto,false);
    }
    
    //Función para pasar datos de una persona al form de chofer
    function datosChofer(objeto){
        $('#personaDialog').dialog('close');
        $.post("",{verificarCho:true,Prs_Cod:objeto.Prs_Cod},function(response){
            if(response['existe']===true){
                $.alert('La persona ya se encuentra registrada como chofer..!!');
                $('#frm_cho')[0].reset();
                return;
            }
            $('#frm_cho').setData(objeto,false);
            $('#Ciu_Cod').val(objeto['Ciu_Cod']).trigger('chosen:updated');
        },'json').fail(function(){$.alert();});
    }
    
    //Función para cargar datos de los viajes pertenecientes a un cliente
    function datosViaje(objeto){
        $('#viajeDialog').dialog('close');
        $('#Via_Grid').jqGrid('clearGridData',true);//.trigger('reloadGrid');
        $.post("",{cargarViajes:true,Cli_Cod:objeto.Cli_Cod, Fecha:$('#por_fecha').is(':checked')?'S':'N', Fec_Ini:$('#Fec_Ini').val(), Fec_Fin:$('#Fec_Fin').val()},function(response){
            $.each(response,function(i,v){
                agregarFila(v['Via_Cod']);
                $('#Via_Grid').find('tr#'+v['Via_Cod']).setData(v,false);
                $('#frm_via').setData(objeto,false);
                $('#btn_agr').removeClass('ui-state-disabled');
            });
        },'json').fail(function(){$.alert();});
    }
    
    //Función para guardar datos
    function saveDatos(frm,save,dialogo){
        var data=$('#'+frm).getData('save');data[save]=true;
        if(dialogo==='cargamento'){if($('#Ite_Lar').val()===''){$.alert('Debe seleccionar un producto..!!');return;}}
        $.post("",data,function(response){
            if(response.success===true){
                if(dialogo==='cargamento'){
                    $('.select_carga').append($('<option>', {value: response['registro']['Car_Cod'],text: response['registro']['Car_Des']}));
                }
                if(dialogo==='modo'){
                    $('.select_modo').append($('<option>', {value: response['registro']['Mot_Cod'],text: response['registro']['Mot_Des']}));
                }
                if(dialogo==='chofer'){
                    arr_cho.push(response['registro']);
                }
                if(dialogo==='automotor'){
                    arr_aut.push(response['registro']);
                }
                $('#'+frm)[0].reset();$('#'+dialogo+'Dialog').dialog('close'); return;
            }
        },'json').fail(function(){$.alert();});
    }