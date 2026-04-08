function imprimeGastos(){
        $('#tablaimpaApo').html($('#list').jqGrid('exportGridInnerHTML',{footer:true,generated:false,removeHiddens:true,removeCols:[0,1,7]}));
        $('#imprimir').printElement();
}
function saveForm() {       		
if($('#opn').val()!=='C' && $('#docu').val()===''){$.alert("No ha seleccionado destinatario de cheque!");return;}
if($('#monto_rep').val()===''){	$.alert("No ha seleccionado comprobantes para la reposici&oacute;n!");	return;	}

var datos=$('#formReposi').getData('save');
datos['gridDatos']=$('#list').getGridBatch();
$.post("",datos, 
        function(response){							
                if(response['success']===true){
                        $('#list').trigger('reloadGrid');
                        $('#nomBan').html($('#bancos').find('option:selected').text());							

                        if(response['paramACT']==='C'){
                                var html=$('#modelo').html();
                                html = html.replace(/{link}/g,response['paramChe'] );
                                $('#printCheque').html(html).css('display','');
                                $('#successDialog').dialog("option", "height", 250);
                        } else {
                                $('#printCheque').css('display','none');
                                $('#successDialog').dialog("option", "height", 150);
                        }
                        $.alert("El Registro se ha Guardado con Exito!");					

                        $('#impReposi').attr('href','cch_pri_reposicion_1.0.php?Rep_Cod='+response['Rep_Cod']+'&opn='+response['paramACT']);
                        $('#impCompr').attr('href','../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo='+response['Com_Cod']+'&tabla=proveedore&campo=Prv_Cod&tipo='+response['Tia_Cod']+'&Pec_Cod='+response['Pec_Cod']);											
                        $('#successDialog').dialog('open');

                        $('#chequeG').css('display','');$('#bancosG').css('display','');$('#btnBusca').css('display','');
                        $('#bancos').attr('required');$('#Che_Num').attr('required');$('#docu').attr('required');

                        $('#formReposi')[0].reset();
                        getDataReposicion();
                }else{$.alert(response['message']);}
        },'json').fail(function(error) {
                $.alert("El Servidor ha fallado en responder!");
        });
}
    
    //funcion que me permite sumar los valores chequeados y pasarlos a una caja de texto
    function updateSaldos(grid,el){
	var rows= grid.jqGrid('getRowData');
	var suma=0;
	
        for(var i=0;i<rows.length;i++){                             
            if(rows[i].act==="Yes"){
                //console.log(el);
                if((suma+parseFloat('0'+rows[i].total))<= $("#monto").val())
                {
                    suma=suma+parseFloat('0'+rows[i].total);				
                }
                else
                {					
                    $("#list").jqGrid("setCell", el, "act","No");					
                    //$(el).removeAttr("checked");
                    alert("La reposicion no puede sobrepasar los $" + $("#monto").val()); 
                    return;
                }
            }            
        }		                       
        $("#monto_rep").val(suma.toFixed(2)); //redondeamos a 2 decimales y pasamos el valor a la caja de texto
        $('#monto_rep_tot').val(suma.toFixed(2));
        rows[0].act="No";
    }
    
    $(document).ready(function()
    {
        var jgrid=$("#list");
        $("#chngroup").change(function()
        {
            var vl = $(this).val();
            if(vl) 
            {
                if(vl === "clear") 
                {
                    grid.jqGrid('groupingRemove',true);
                } 
                else 
                {
                    grid.jqGrid('groupingGroupBy',vl);
                }
        }
	});
	jgrid.jqGrid({
		url: '',
		mtype: "GET", datatype: "json", regional : 'es',//ajaxRowOptions: { async: true },
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
                                        // lineas comentadas para que no reste el valor de la retencion al total, descomentarlas si en un futuro se desea que reste el valor de la retencion al total
                                        // if(parseFloat(rowObject.total) && rowObject.asu==='N'){
                                        //         rowObject.total = parseFloat(rowObject.total) - (parseFloat('0'+rowObject.ret));
                                        // }else{
                                                rowObject.total = parseFloat(rowObject.total);
                                        // }
                                        return $.fn.fmatter.call(this, "currency", rowObject.total, options);
                                },
                                unformat: function (cellValue, options, cell) {var opt = $.extend(true, {}, options);opt.colModel.formatter = "currency";delete opt.colModel.unformat;return $.unformat.call(this, cell, opt);},
                                summaryTpl: "Total: {0}",summaryType: "sum" // set the formula to calculate the summary type 
			},
			{ label:'<center><i class="ui-icon ui-icon-circle-check"></i></center>', name: 'act', width: 10, align: 'center',viewable: false, formatter: 'checkbox',
                                formatoptions: { disabled: false },resizable:false
                        },
		],                                                     
		rowNum: 100000000,pager: "#listPager", gridview: true, rownumbers: false, viewrecords: true, altRows: true, altclass: "myAltRowClass",pginput : false,pgbuttons: false,  pgtext: "Mostrando {0} Documentos.",
		footerrow: true, userDataOnFooter: false,							
		loadComplete: function () {                       
			jgrid.jqGrid('footerData', 'set', { total:jgrid.jqGrid('getCol','total',false,'sum')});							   
			
			var grid=$(this), iCol = grid.getColumnIndexByName('act'), rows = this.rows, i, c = rows.length;			
			var suma = 0;
                        var row = grid.jqGrid('getRowData');
			for (i = 0; i < c; i++) { 
                            if ( i < row.length){
                                suma += Number.parseFloat(row[i].total);
                            }
                            
                            $(rows[i].cells[iCol]).click(function (e) { 											
                                    updateSaldos(grid,$(this).parent().attr('id'));
                            });
			}
                        $('#fac_val').val(suma.toFixed(2));
                        $('#sal_act').val(  (   Number.parseFloat($('#monto').val()) - suma).toFixed(2));
		}                          			
	});                        
		jgrid.navGrid('#listPager',{ edit: false, add: false, del: false, search: false, refresh: true, view: true, position: "left", cloneToTop: false });
		jgrid.jqGrid('bindKeys');
		$.createDialog('#successDialog',150,550);
	});
        
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
        
        function selectProvee(data){                           
            if(typeof data==='undefined'){                                
                    $("input[name='Prv_Cod']").val('');
                    $("#docu").val('');                                
                    return false;
            } else {                            
                    $("#docu").val(data['proveedor']);                             
                    $("input[name='Prv_Cod']").val(data['Prv_Cod']);                                     
                    $("#provDialog").dialog("close");
            }
        }
        
        $(document).ready(function(){              
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
        });
        
        function resetForm() {
            $("select[name='bancos']").val('');
            $("select[name='Tia_Cod']").val('');
            $("#Che_Num").val('');
            $("#docu").val('');		
        }
    
        function setCheques(Ban_Cod) {
            var datBan=Ban_Cod.split('*');
            $.get('',{'Ban_Cod':datBan[0],'numCheIni':true}, function(response){
                   if(response['success']===true){
                           var numChe=(response['Che_Num']*1)+1;
                           $("#Che_Num").val(numChe).alertMsg();
                   }else {numChe=0;$("#Che_Num").val(numChe);$.alert("No se logro obtener n&uacutemero del cheque");}
           },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});
        }
	
        function validaCheque(numero){
            if ($('#Che_Num').val() !== ""){
                var valBanco=$("#bancos").val();
                var banco_val = valBanco.split("*",2);
                var numAnt=$("#Che_Num").val();
                $.postDataJson('',{numero:numero,banco:banco_val[0],valChe: true}, function(response){
                        if(response['success']===true){
                                if(response['valid']===false){
                                        numChe=(response['Che_Num']*1)+1;
                                        $("#Che_Num").val(numChe);$.alert('El Cheque <b>No. '+numAnt+'</b> ya existe.');
                                }
                                else{$("#Che_Num").alertMsg();}
                        }else {numChe=0;$("#Che_Num").val(numChe);$.alert("No se logro obtener n&uacutemero del cheque");}                                
                });
            }            
        }
        
        $( document ).ready(function() {
            $("#Cop_Num").mask("999-999-999999999",{placeholder:"_"});
            $.createDatePickers('.dateType');
            //$('#Cop_Fec').datepicker( "option", "maxDate", '<?php echo $maximo; ?>');
        });