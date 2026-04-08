
    var numeroChequeActual = 0;
    var suma = 0, valorActual = 0;
    $('#fecha').createDatePickers();    
    /* inicio */

    window.onload = inicio;

    function inicio(){
        getDataReposicion();
        $('#ini').createDatePickers( new Date());
        $('#fin').createDatePickers( new Date());
    }
    
    function getDataReposicion() {
        $.getDataJson('',{getTotal:true},function(res){
            var total = 0;
            var monto_caja = Number.parseFloat($('#monto').val());
            if (res.data.length > 0){
                $.each(res.data, function (i, item){
                    total += Number.parseFloat(item.total);
                });
                $('#sal_act').val((monto_caja - total).toFixed(2));
            } else {
                $('#sal_act').val((monto_caja - total).toFixed(2));
            }
        });
    }
    
    function saveForm() {
        if($('#docu').val()!='') {
            if( Number.parseFloat($('#monto_rep').val())=== Number.parseFloat(valorActual)) {
                $.alert("No ha seleccionado comprobantes para la reposici&oacute;n!");
            } else {
                var datos=$('#formReposi').getData('hdd_save');
                datos['gridDatos']=$('#list').getGridBatch();
                $.post("",datos, 
                function(response) {
                    if(response['success']===true) {
                        $('#list').clearGridData(true);
                        $('#formReposi')[0].reset();
                        if (response['paramChe'] !== "") {
                            var html=$('#modelo').html();
                            html = html.replace(/{link}/g,response['paramChe'] );
                            $('#printCheque').html(html).css('display','');
                        } else {
                            $('#printCheque').css('display','none');
                        }

                        $('#nomBan').html($('#bancos').find('option:selected').text());
                        $.alert("El Registro se ha Guardado con Exito!");
                        $('#successDialog').dialog("option", "height", 250);
                        $('#impReposi').attr('href','cch_pri_reposicion_1.0.php?Rep_Cod='+response['Rep_Cod']);
                        $('#impCompr').attr('href','../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo='+response['Com_Cod']+'&tabla=proveedore&campo=Prv_Cod&tipo='+response['Tia_Cod']+'&Pec_Cod='+response['Pec_Cod']);											
                        $('#successDialog').dialog('open');
                    } else {
                        $.alert(response['message']);
                    }
                },'json').fail(function(error) {$.alert("El Servidor ha fallado en responder!");});   
            }				
        } else {
            $.alert("No ha seleccionado destinatario de cheque!");
        }
    }

    function resetForm(){
		$("select[name='bancos']").val('');
		$("select[name='Tia_Cod']").val('');
		$("#Che_Num").val('');
		$("#docu").val('');		
	}	
	
    function setCheques(Ban_Cod){ 
        var datBan=Ban_Cod.split('*');
        $.get('',{'Ban_Cod':datBan[0],'numCheIni':true}, function(response){
            if(response['success']===true){
                    var numChe=(response['Che_Num']*1)+1;
                    $("#Che_Num").val(numChe).alertMsg();
            } else {
                numChe=0;$("#Che_Num").val(numChe);
                $.alert("No se logro obtener n&uacutemero del cheque");
            }
        },'json').fail(function(error) {
            $.alert("El Servidor ha fallado en responder!");
        });
    }
	
    function validaCheque(numero) {
        if ($('#Che_Num').val() !== "" && numero !== numeroChequeActual ) {
            var valBanco    =   $("#bancos").val();
            var banco_val   =   valBanco.split("*",2);
            var numAnt      =   $("#Che_Num").val();				 
            $.postDataJson('',{numero:numero,banco:banco_val[0],valChe: true}, function(response){
                if(response['success']===true){
                    if(response['valid']===false){
                            numChe=(response['Che_Num']*1)+1;
                            $("#Che_Num").val(numChe);$.alert('El Cheque <b>No. '+numAnt+'</b> ya existe.');
                    }else{
                        $("#Che_Num").alertMsg();
                    }
                } else {
                    numChe=0;
                    $("#Che_Num").val(numChe);
                    $.alert("No se logro obtener n&uacutemero del cheque");
                }
            });
        }
    }
	
    $(document).ready(function() {               
        $.createSearchDialog('#provDialog',[
            { label: 'Cód.Int.', name: 'Prv_Cod', key: true,hidden:true,viewable: true },                                
            { label: 'C&eacute;dula/R.U.C.', name: 'Prs_Ced', width: 50 },                      
            { label: 'Proveedor', name: 'proveedor', width: 190, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
            { label: 'Direcci&oacute;n', name: 'Prs_Dir',hidden:true,viewable: true },                      
            { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                formatter:function (cellvalue, options, rowObject) { 
                        var clic='selectProvee($("#provGrid").jqGrid("getRowData",'+rowObject.Prv_Cod+'))';
                        return  '<span class="btn btn-success btn-xs" title="Seleccionar" onclick=\''+clic+'\'><i class="glyphicon glyphicon-arrow-right"></span>'; 
                }
            }
        ]); 
        $.createSearchDialog('#reposiDialog',[
            { label: 'Cod.Int.', name: 'Rep_Cod',width: 20, key: true,viewable: true },
            { label: 'Tia_Cod', name: 'Tia_Cod',width: 20, hidden:true},
            { label: 'Pld_Cod', name: 'Pld_Cod',width: 20, hidden:true},
            { label: 'Ban_Cod', name: 'Ban_Cod',width: 20, hidden:true},
            { label: 'Prs_Nom', name: 'Prs_Nom',width: 20, hidden:true},
            { label: 'Prs_Ape', name: 'Prs_Ape',width: 20, hidden:true},
            { label: 'Prv_Cod', name: 'Prv_Cod',width: 20, hidden:true},
            { label: 'Rep_Obs', name: 'Rep_Obs',width: 20, hidden:true},
            { label: 'Com_Cod', name: 'Com_Cod',width: 20, hidden:true},
            { label: 'Fecha', name: 'Rep_Fec', width: 30 },
            { label: 'Banco', name: 'Pld_Des', width: 100, cellattr: function (rowId, tv, rawObject, cm, rdata) { return 'style="white-space: normal;"'; }},                   
            { label: 'Cheque', name: 'Che_Num', width: 30,viewable: true },
            { label: 'Monto', name: 'Com_Val', width: 20,viewable: true },
            { label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 18, align: 'center',viewable: false,
                formatter:function (cellvalue, options, rowObject) { 
                    var clic='cargasDatosRepos($("#reposiGrid").jqGrid("getRowData",'+rowObject.Rep_Cod+'))';
                    return  '<span class="btn btn-success btn-xs" title="Seleccionar" onclick=\''+clic+'\'><i class="glyphicon glyphicon-arrow-right"></span>'; 
                }
            }
        ]);
    }); 
	
    function cargasDatosRepos(data){		
        if(typeof data==='undefined') {
            $("input[name='RepCod']").val('');
            $("#docu").val('');                                
            return false;
        } else {
            if (data.Ban_Cod !== "") {
                $('#opm').prop("disabled",true);
                $('#opn').prop("checked",true);
                $('#opn').prop("disabled",false);
                $('#cheque').css('display','');
                $('#btnBusca').css('display','').prop("disabled",false);
                $('#banco').css('display','').prop("disabled",false);
                //                     
                $("#bancos").val(data['Ban_Cod']+'*'+data['Pld_Cod']);
                $("#Che_Num").val(data['Che_Num']);
                numeroChequeActual = data.Che_Num;
            } else {
                $('#opm').prop("disabled",false);
                $('#opm').prop("checked",true);
                $('#opn').prop("disabled",true);
                $('#cheque').css('display','none').prop("disabled",true);
                //$('#btnBusca').css('display','none');
                $('#banco').css('display','none').prop("disabled",true);
            }
            $("#PrvCodBus").val(data['Prv_Cod']);
            $("#obs").val(data['Rep_Obs']);
            $("#Com_Cod").val(data['Com_Cod']);                
            $("#monto_rep").val(data['Com_Val']);
            valorActual = data['Com_Val'];
            $("#RepCod").val(data['Rep_Cod']);
            $("#Tia_Cod").val(data['Tia_Cod']);
            $("#fecha").val(data['Rep_Fec']);
            $("#docu").val(data['Prs_Ape']+' '+data['Prs_Nom']);
            $("#list").Search("#formReposi","ajaxSubgrid");
            $("#reposiDialog").dialog("close");
        }
    }
	
    function selectProvee(data){
        if(typeof data==='undefined') {
            $("input[name='Prv_Cod']").val('');
            $("#docu").val('');
            return false;
        } else {
            $("#docu").val(data['proveedor']);
            $("input[name='Prv_Cod']").val(data['Prv_Cod']);
            $("#provDialog").dialog("close");
        }
    }
	
    function loadXML(){
		var formData = new FormData(document.getElementById("form3"));
		formData.append("uploadXML", true);
		$("#loader").show();
		//formData.append(f.attr("name"), $(this)[0].files[0]);
		$.ajax({
			url: "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",
			type: "post", dataType: "json", data: formData, cache: false, contentType: false, processData: false
		}).done(function(response){
			$("#loader").fadeOut("slow");
			if(response['success']===true){
				$("#list").jqGrid("clearGridData");  
				$("#list").jqGrid("setCaption",response['empresa']);
				$("#list").jqGrid('setGridParam',{rowNum:response['grid']['records']});
				$("#list").jqGrid('setGridParam', {data:response['grid']['rows'],page:1,records:response['grid']['records'],total:response['grid']['total'] }).trigger('reloadGrid');
				$("#form3").effect("highlight",{},500);
			}else{$("#list").jqGrid("clearGridData");$.alert(response['message']);}                                  
		}).fail(function(error) { $.alert("El Servidor ha fallado en responder! "); $("#loader").hide();});                              
	}   
	
	$(document).ready(function () {
		var jgrid=$("#list");
		$("#chngroup").change(function(){
            var vl = $(this).val();
            if(vl) {
                if(vl === "clear") {jgrid.jqGrid('groupingRemove',true);} 
                else {jgrid.jqGrid('groupingGroupBy',vl);}
            }
        });
	jgrid.jqGrid({
		url: '',
		mtype: "get", datatype: "local", regional : 'es',//ajaxRowOptions: { async: true },
		postData: $("#formReposi").getData("ajaxSubgrid"),
		autowidth : true, shrinkToFit: true, height: 250,caption:'Facturas de Reposici&oacute;n',responsive:true,hidegrid:false,
		colModel: [				
			{ label: 'Cod. Int.', name: 'Cop_Cod',key: true, width: 20,align:"center"},							
			{ label: 'C.I/R.U.C', name: 'Prs_Ced', width: 30,align:"center"},			
			{ label: 'Proveedor', name: 'provee', width: 120},   
			{ label: 'Tipo', name: 'Tic_Des', width: 50,align:"center"}, 
			{ label: 'Fecha', width: 30,name: 'Cop_Fec',align:"center", sorttype:"date"},								
			{ label: 'Num. Comprobante', name: 'Cop_Num', width: 50,align:"center"},				
			{ label: 'Importe', name: 'total', width: 35, align: 'right', formatoptions: { prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'},summaryTpl: "{0}", summaryType: "sum",summaryRound:'2', summaryRoundType: 'round',      // set the formula to calculate the summary type                                        
                formatter: function (cellValue, options, rowObject) {
                    if(parseFloat(rowObject.total) && rowObject.asu==='N'){
                        rowObject.total = parseFloat(rowObject.total) - (parseFloat('0'+rowObject.ret));
                    } else {
                        rowObject.total = parseFloat(rowObject.total);
                    }
                    return $.fn.fmatter.call(this, "currency", rowObject.total, options);
                },
                unformat: function (cellValue, options, cell) {var opt = $.extend(true, {}, options);opt.colModel.formatter = "currency";delete opt.colModel.unformat;return $.unformat.call(this, cell, opt);},
                summaryTpl: "Total: {0}",summaryType: "sum" // set the formula to calculate the summary type 
			},
			{ label:'<center><i class="ui-icon ui-icon-circle-check"></i></center>', name: 'act', width: 10, align: 'center',viewable: false, formatter: 'checkbox',
                formatoptions: { disabled: false },resizable:false
            },
            { label: '<i class="glyphicon glyphicon-trash"></i>', name: 'btn_remover', width: 20, align: 'center', viewable: false, formatter: gridButtonFormatter,
                formatoptions: {
                    action: 'liberarCompra', title: 'Eliminar Compra', icon: 'trash', type: 'danger'
                }
            }
		],                                                     
		rowNum: 100000000,pager: "#listPager", gridview: true, rownumbers: false, viewrecords: true, altRows: true, altclass: "myAltRowClass",pginput : false,pgbuttons: false,  pgtext: "Mostrando {0} Documentos.",
		footerrow: true, userDataOnFooter: false,							
		loadComplete: function () {
			jgrid.jqGrid('footerData', 'set', { Cop_Num: '<div style="text-align:right;width:100%;">TOTAL:</div>', total: jgrid.jqGrid('getCol', 'total', false, 'sum') });
			var grid=$(this), iCol = grid.getColumnIndexByName('act'), rows = this.rows, i, c = rows.length;
            var row = grid.jqGrid('getRowData');
			suma = 0;
			for (i = 0; i < c; i++) {
                if ( i < row.length){
                    suma += Number.parseFloat(row[i].total);
                }
                $(rows[i].cells[iCol]).click(function (e){
                        updateSaldos(grid,$(this).parent().attr('id'));

                });
			}
            // $('#fac_val').val(suma.toFixed(2));
            // muestra la suma total de la repisicion actual
            $('#monto_rep_tot').val(suma.toFixed(2));
		}
	});
		jgrid.navGrid('#listPager',{ edit: false, add: false, del: false, search: false, refresh: true, view: true, position: "left", cloneToTop: false });
		jgrid.jqGrid('bindKeys');
		$.createDialog('#successDialog',150,550);
		//$.createDialog('#reposiDialog',400, 700);
	});
    
	//funcion q me permite sumar los valores chequeados y pasarlos a una caja de texto
	function updateSaldos(grid,el){
    var rows= grid.jqGrid('getRowData');
    var sumaT=0;
        for(var i=0;i<rows.length;i++){
            if(rows[i].act==="Yes"){
                console.log(el);
                if((sumaT + parseFloat('0'+rows[i].total))<= $("#monto").val()){
                    sumaT= sumaT + parseFloat('0'+rows[i].total);
                } else {					
                    $("#list").jqGrid("setCell", el, "act","No");                    
                    alert("La reposicion no puede sobrepasar los $" + $("#monto").val());
                    return;
                }
            }
        }
        $("#monto_rep").val(sumaT.toFixed(2)); //redondeamos a 2 decimales y pasamos el valor a la caja de texto
        rows[0].act="No";
	}

    // funcion de liberacion de la reposicion - nueva
    function liberarReposicion() {

        if (!confirm("¡Adventencia!\nEsta a punto de liberar toda la reposicion de caja Chica\n¿Desea continuar?")) {
            return; // Si cancela, no hace nada
        }
        var reposi = $("#RepCod").val();
        var compr = $("#Com_Cod").val();
        console.log(compr);
        
        $.saveDataJson("", { liberarAjax: true, RepCod: reposi , Com_Cod: compr}, function (responce) {
            if (responce['success']) {
                // $.alert("Se ha liberado la reposici&oacute;n con &eacute;xito!");
                $('#reposiGrid').jqGrid('setGridParam',{datatype:'json'}).trigger('reloadGrid');
                $('#list').clearGridData(true);
                resetForm();
                $("#RepCod").val('');
                $("#Com_Cod").val('');
                $("#monto_rep").val('');
                $("#monto_rep_tot").val('');
                $("#fecha").val('');
                $("#docu").val('');
                $("#obs").val('');
            } else {
                $.alert(responce['message']);
            }
        });
    }
    // funcion para liberar compra de la reposicion
    function liberarCompra(data) {
        if (!confirm("¡Adventencia!\nEsta a punto de liberar la factura anexa a esta reposicion\n¿Desea continuar?")) {
            return; // Si cancela, no hace nada
        }
        
        $.saveDataJson("", { liberaCompraAjax: true, Cop_Cod: data['Cop_Cod'] }, function (responce) {
            if (responce['success']) {
                $('#list').jqGrid('setGridParam', { datatype: 'json' }).trigger('reloadGrid');
            } else {
                $.alert(responce['message']);
            }
        });
    }

    // Ocultar el botón de eliminar si Dre_Tip es 'P' y Rep_Cod es 0
    function gridButtonFormatter(cellvalue, options, rowObject) {
        if (rowObject.Dre_Tip === 'P' && (rowObject.Rep_Cod === 0 || rowObject.Rep_Cod === '0')) {
            return '';
        }
        // Usa el formateador original si no se cumple la condición
        if ($.fn.fmatter && $.fn.fmatter.gridButton) {
            return $.fn.fmatter.gridButton.call(this, cellvalue, options, rowObject);
        }
        return cellvalue;
    }
