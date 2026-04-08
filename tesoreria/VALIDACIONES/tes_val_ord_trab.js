var editDoc=false,AutCod='',TicCod='',Com_Asoc=0;
var Nota_CreDeb=false,Mod_Nota_CreDeb=false;
var pago_min=0;
var edit_reten=false;
var doc_ventas='';
var init_load=false;
const ret_banano=[338];
var list_printers = $.getLocalStore('printers') || {}, printers = list_printers['has_printers'] === true;

function inicio(){
  $("#Pac_Fna").createDatePickers();
  $("#Ord_Fec").createDatePickers();
   //numFicha();
}

    window.onload = inicio;
    var data = "";
    var tipoDoc = 0;

function ImpFicha(rowFicha){
            $.getDataJson('',{'cargarReportes':true},function(res){
                var reportes=res['reportes'];
                $.varValid(reportes[1])?$.imprimirUrl(reportes[1]+'?Fic_Cod='+rowFicha.Fic_Cod):$.alert('Sin Reportes Asociados');
            },function(err){
                console.log(err['message']);
            });
        }
        
function inicializarDocVenta(nuevo_doc=true){
   $(function(){

      //$('#Tic_Cod').on('change',verifyButtonAutExtern);


      //let element=$('input[name="input_autorizacion"]');
      //element.toggle();
      // agregando opcion de edicion de autorizacion a modo manual
      //$('#btnAddAut').on('click',{'elem':element},showIntutAut);

      $('.datepickers').createDatePickers({checkAvailability:true,hideMsg:false}).mask('9999-99-99',{placeholder:'_'});
      $('#pagosDialog').createDialog({height:325,icon:'usd'});
      $('#changeReteDialog').createDialog({height:128,width:300,icon:'usd'});
      // $.createDatePickers('.datepicker');

      $('#For_Cod').on('change',function (){
         $('.pagoCredito')[this.value*1===2?'show':'hide']();
         (('0'+this.value)*1===2?$('#Cpp_Ven').attr('required','required'):$('#Cpp_Ven').removeAttr('required'));
      });

      $('#OrderBy').on('change',function(){
         $('input[name=order]').val($(this).val());
         $('#serachDocDorm').formSubmit();
      });


      // cargar Tpc_Cod por documentos en ultima version de Facturacion electronica


      if(Mod_Nota_CreDeb){
         cargar_Documentos([4,5]);
      }


      if(Nota_CreDeb){
         $('#Pec_Cod,#For_Cod_Nota').on('change',function(){
            var ventasIds=doc_ventas.getDataIDs();
            if($('#For_Cod_Nota').val()*1===1 && ventasIds.length>=1){
               $.each(ventasIds,function(index,valor){
                  if(index!==0){
                     doc_ventas.delRowData(valor);
                  }
               });
            }else{
               doc_ventas.clearGridData();
            }
            doc_ventas.trigger('reloadGrid');
         });

         $('#For_Cod_Nota').on('change',function(){
            filtrarCuentasFormasPago($(this).find(':selected').data());
            $('#Forma_Cod').val($(this).find(':selected').data('For_Cod'));
         });


         cargarFormasPago();

         doc_ventas=$('#ventas');
         doc_ventas.createGrid({
            data:[], rowNum: 10000000, height: 'auto', footerrow:true,
            colModel:[
               { label: 'C&oacute;d.Int.', name: 'Vet_Cod', key: true, width: 20, align:'center', hidden:false ,classes:'bgNoRight' },
               { label: 'N./Venta.', name: 'Vet_Num', width: 30, align:'center' ,hidden:true,classes:'bgNoRight' },
               { label: 'Numero.', name: 'Vet_Num_Asoc', width: 70, align:'center' ,classes:'bgNoRight' },
               { label: 'C*pagar', name: 'Cpc_Cod', width: 70, align:'center' ,hidden:true,classes:'bgNoRight' },
               { label: 'Fecha/Venta.', name: 'Caj_Fec', width: 40, align:'center' ,classes:'bgNoRight' },
               { name: 'Tic_Des', hidden:true},
               { label: 'Pagos', name: 'pagos', width: 30, classes:'bgNoRight',align:'center',hidden:true, formatter: function(cv,opc,rObj){return $('<div></div>').attr('data--arreglo',$.jsonParser(rObj.pagos)).prop('outerHTML');},unformat:function(el,opts,cell){return $('div',cell).data('Arreglo');}},
               { label: 'Documento', name: 'Tic_Cod', width: 30, classes:'bgNoRight',align:'center', formatter: function(cv,opc,rObj){return $('<div></div>').append(rObj.Tic_Des).attr('data--Tic_-Cod',cv).prop('outerHTML');},unformat:function(el,opts,cell){return $('div',cell).data('Tic_Cod');}},
               { label: 'Tipo/Pago', name: 'For_Des', width: 30, classes:'bgNoRight' },
               { label: 'Cod_Pago', name: 'For_Cod', width: 30, classes:'bgNoRight',hidden:true },
               { label: 'Total', name: 'Vet_Total', width: 30, classes:'bgNoRight', formatter: function(cv, opts, rObj){ return $.toFixed(cv); }  },
               { label: 'Pagos', name: 'Vet_Abonos', width: 30 , formatter: function(cv, opts, rObj){ if(!$.varValid(cv)){cv=0.00};return ($.isNumeric(cv)?$.toFixed(cv):cv); }},
               { label: 'Saldo', name: 'Vet_Saldo',width: 30, align: 'right', formatter:'currency', classes:'bgNoColor' },
               { label: '<i class="glyphicon glyphicon-remove"></i>', name: 'btn_remover', width: 20, align: 'center', formatter:'gridButton',
                  formatoptions:{ action:deleteDocVenta, data:function(o){ return o.Vet_Cod; },
                     icon:'remove', type:'danger' }
               }
            ], loadComplete:function(){ $(this).setGridSummary(['Vet_Saldo'],{Vet_Abonos:'<div style="text-align:right;">TOTAL:</div>'}); }
         },true,'ventas1Pager',{view:false}).gridButtonsAdd([
            {caption:'Agregar',buttonicon:'glyphicon glyphicon-plus',class:'a', onClickButton: function(){ mostrarVentas(); } }
         ]);


         $.createSearchDialog('ventasDialog',[
            { label: 'C&oacute;d.Ven.', name: 'Vet_Cod', key: true, width: 15,align:'center',hidden:true },
            { label: 'Vet./Num.', name: 'Vet_Num', width: 50 },
            { label: 'Cliente.', name: 'cliente', width: 250 },
            { label: 'Fecha/Venta.', name: 'Caj_Fec', width: 90 ,align:'center' },
            { label: 'T.Pago', name: 'For_Des', width: 80 },
            { label: 'Tipo/Doc.', name: 'Tic_Cod', width: 60 ,formatter: function(cv,opc,rObj){return $('<div></div>').append(rObj.Tic_Des).attr('data--Tic_-Cod',cv).prop('outerHTML');},unformat:function(el,opts,cell){return $('div',cell).data('Tic_Cod');}},
            { label:'&nbsp;', name: 'act1', width: 30, align: 'center',viewable: false, formatter:'gridButton', formatoptions:{action:selectVent} }
         ],null,null,null,null,{ title:'N&uacute;mero de Venta',label:'Num. Doc.', options:[] });
      }





      //Change para el cambio de periodo
      $('#Pec_Cod').change(function(){
         var sel_fecha=$(this).find('option:selected');
         //console.log(sel_fecha.data('inicio')+'*'+sel_fecha.data('fin')+'*'+sel_fecha.data('placod'));
         fechas(sel_fecha.data('inicio'),sel_fecha.data('fin'),sel_fecha.data('placod'));
         cuentas(sel_fecha.data('placod'));
         $('#Caj_Fec').trigger('change');
      });

      if(nuevo_doc){
         $('#Pec_Cod').trigger('change');
      }


      $('#cambiarAut').on('click',function(){
         $('#autorizaForm').setData({'Tic_Cod':$('#Tic_Cod').find('option:selected').data('ticcod'),
            'Pun_Cod':$('#Tic_Cod').find('option:selected').data('puncod')});
         $('#autorizaDialog').dialog('open');
         $.Search('autoriza');
         //$('#autorizaGrid').trigger( 'reloadGrid' );

      });

      $('#Pag_Cod').on('change',function (){
         var text=$(this).find('option:selected').text().toUpperCase();
         $('.cuen_ban,.banco,.bancos,.obs_credito').find(':input').removeAttr('required').end().hide().setData({});
         $('.cuenta_pago').show().find(':input').attr('required','required');
          $('.fecha_cheque').hide().removeAttr('disabled');
         switch(text){
         case 'DEPOSITO':
         case 'TRANSFERENCIA':
            $('.banco,.cuen_ban').show().find(':input').attr('required','required');
            $('.cuenta_pago').hide().removeAttr('disabled');
            break;
         case 'CHEQUE':
            $('.bancos,.cuen_ban').show().find(':input').attr('required','required');
            $('.fecha_cheque').show().find(':input').attr('required','required'); 
            $('#Ban_Cod').trigger('change');
            $('#Vet_Cue').removeAttr('disabled');
            break;
         default:
            break;
         }
      }).trigger('change');

      $('#For_Cod').on('change',function (){
         var credi=('0'+this.value)*1===2, val=$('#Pag_Cod').find('option').hide().end().find('option[data-forcod="'+this.value+'"]').show()[0].value;
         $('#Pag_Cod').val(val).trigger('change');
         $('.pagoCredito')[credi?'show':'hide']();
         (credi?$('#Cpc_Ven').attr('required','required'):$('#Cpc_Ven').removeAttr('required'));
         (credi?$('#saldo_pago').attr('readonly','readonly'):$('#saldo_pago').removeAttr('readonly'));
         //backupHeader();
         var sinAcento=remove_accent($(this).find('option:selected').text());
         cargarCuentas(sinAcento,$('#Pag_Cod').find('option:selected').data('forcod'));
      }).trigger('change');

      //cargarCuenta();
      items=$('#items');
      pagos=$('#pagos');
      pagos.createGrid({
         data:[], caption: 'Pagos', rowNum: 10000000, height: 'auto', footerrow:true,
         colModel:[
            { label: 'C&oacute;d.Int.', name: 'Vet_Num', key: true, width: 15, align:'center', hidden:true },
            { label: 'fecha_ven.', name: 'Cpc_Ven', width: 15, align:'center', hidden:true },
            { label: 'Ban_Cod.', name: 'Ban_Cod', width: 15, align:'center', hidden:true },
            { label: 'Forma', name: 'For_Cod', width: 30, classes:'bgNoRight', formatter: function(cv, opts, rObj){ return '<div data-val="'+cv+'">'+$('#For_Cod option[value="'+cv+'"]').text()+'</div>'; }, unformat:function(el, opts, cell){ return $('div', cell).data('val'); } },
            { label: 'Forma_Cod', name: 'Forma_Cod', width: 30,hidden:true, classes:'bgNoRight' },

            { label: 'Fec_che', name: 'Fec_che', width: 30,hidden:true, classes:'bgNoRight' },
            { label: 'Bak_Cod', name: 'Bak_Cod', width: 30,hidden:true, classes:'bgNoRight' },

            { label: 'Tipo', name: 'Pag_Cod', width: 30, classes:'bgNoRight', formatter: function(cv, opts, rObj){ return $('#Pag_Cod option[value="'+cv+'"]').text(); }  },
            { label: 'Tipo_Cod', name: 'Tipo_Cod', width: 30,hidden:true, classes:'bgNoRight', formatter: function(cv, opts, rObj){ return $('#Pag_Cod option[value="'+cv+'"]').val(); }  },
            { label: 'Pag_Pld', name: 'Pag_Pld',width: 30,hidden:true, classes:'bgNoRight' },
            { label: 'Banco', name: 'Vet_Ban', width: 50, align: 'center', classes:'bgNoRight', formatter: function(cv, opts, rObj){ var ban=$.varValid(rObj['Ban_Cod'])&&rObj['Ban_Cod'].length>0?'Ban_Cod':($.varValid(rObj['Bak_Cod'])&&rObj['Bak_Cod'].length>0?'Bak_Cod':null); if($.varValid(ban)) return $('#'+ban+' option[value="'+rObj[ban]+'"]').text(); else return ''; } },
            { label: 'Cta. Banco', name: 'Vet_Cue', width: 50, align: 'center', classes:'bgNoRight'},
            { label: 'Doc./Cheque', name: 'Vet_Che', width: 50, align: 'center'},
            { label: 'Monto', name: 'Vet_Tot', width: 40, align: 'right', formatter:'currency', classes:'bgNoColor'}
         ], loadComplete:function(){ $(this).setGridSummary(['Vet_Tot'],{Vet_Che:'<div style="text-align:right;">TOTAL:</div>'}); }
      });
      $('#documentosPager_center').css('width','0px');

      $('#copresult').createGrid({
         height:75, postData: {CheListAjax:true},caption:'Detalle Venta <button id="btnVentaPrint" onclick="$.imprimirUrl($(this).data(\'url\'))" class="btn btn-success btn-xs pull-right hidden" style="margin-top: -2px;"><i class="glyphicon glyphicon-print "></i> Imprimir</button>',
         rowNum: 10000,
         colModel: [
            { label: 'C&oacute;d.Int.', name: 'Vet_Int', key: true, width: 15,align:'center', hidden:true },
            { label: 'Cantidad ', name: 'Vet_Can', width: 45, align: 'right' },
            { label: 'Item', name: 'Ite_Lar', width: 130  },
            { label: 'P. Unit.', name: 'Vet_Pru', width: 130, align: 'right'},
            { label: 'Importe', name: 'Vet_Imp', width: 65, align: 'right', formatter:'currency', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''},summaryType: 'sum'}
         ],
         loadComplete: function (){ $(this).setGridSummary(['Debe','Haber'],{Glosa:'<div style=\'text-align:right;\'>TOTALES:</div>'}); }
      },true);

      $.fn.fmatter.impRenta=function(cv,opts,cObjt){
         if(!$.varValid(cObjt['Pro_Cod'])||cObjt['Pro_Cod']==='') return '';
         return getRentaButton(cv,{tipo:'R',index:cObjt['index']},cObjt); };
      $.fn.fmatter.impRenta.unformat=$.unformatCellHtml;
      $.fn.fmatter.retIva=function(cv,opts,cObjt){ if(!$.varValid(cObjt['Pro_Cod'])||cObjt['Pro_Cod']==='') return ''; if(cObjt['Iva_Por']*1===0) return ''; return getRentaButton(cv,{tipo:'I',index:cObjt['index']},cObjt);  };
      $.fn.fmatter.retIva.unformat=$.unformatCellHtml;

      addItem({});
      //changeIvas();
      var cont = 0;
      $('#Cli_Btn, #Rgt_Btn').on('click', function () { $('#Prf_Num').text('');$('#Prf_Cod').text('');$('#FPrfNum').hide();$('#items').clearGrid(); updateDocument(); totalAndDescuentoACero();console.log(cont);if (cont <= 1){addItem({});cont++;}else{addItem({});cont = 0;}});
      $('#prof_btn').on('click', function () {  $('#FPrfNum').removeAttr("style"); });
      //$('#Prf_Num').text(prf['Prf_Num']);
      //$('#Prf_Cod').text(prf['Prf_Cod']);

      $('#monto_pago,#saldo_pago').on('keyup',function (){
         var mon=$('#monto_pago').val(),sal=$('#saldo_pago').val(), cam=(!isNaN(mon)&&!isNaN(sal)?$.round(mon)-$.round(sal):0);
         $('#cambio_pago').val($.toFixed(cam));
         $('#cam_sal').removeClass('alert-danger alert-success').addClass(cam<0?'alert-danger':'alert-success').find('b').html(cam<0?'Por Cobrar':'Cambio');
      }).on('change',function (){
         var monto=$(this).attr('id')==='monto_pago', val=$(this).val(),sal=$('#Val_Pcc').val()*1;
         $(this).val(isNaN(val)||val===''?
            (monto?'':$.toFixed(sal)):
            (monto?
               $.toFixed(val):
               (val>sal?
                  $.toFixed(sal):
                  $.toFixed(val)
               )
            )
         ).trigger('keyup');
      });


      //Change para obtener el nmero de secuencia y validar el tamanio del documento
      $('#Tic_Cod').change(function(){
         if(Nota_CreDeb || Mod_Nota_CreDeb){
            doc_ventas.clearGrid();
         }
         var Tic_Sri=$('#Tic_Cod').find('option:selected').data('ticsri')*1, rise=(Tic_Sri===2||Tic_Sri===9);
         var max=$('#Tic_Cod').find('option:selected').data('autima'),its=items.jqGrid('getDataIDs').length;
         var width = items.jqGrid('getGridParam', 'width');

         if(Tic_Sri===0&&pago_min>0&&!init_load){
            $('#Tic_Cod').val(Tic_Cod_Previo).trigger('change');
         }




         if($(this).val()*1>0){
            var tic_cod_sel=$(this).find('option:selected');
            if(tic_cod_sel.data('ticsri')*1===0 && Cof_Con==='S'){
               $('#div_check_comp').removeClass('hidden');
            }else{
               $('#div_check_comp').addClass('hidden');
            }

            var dias_aviso=tic_cod_sel.data('autadv')*1;
            var fecha_caduca=moment(tic_cod_sel.data('autcad'));
            var numero_aviso=tic_cod_sel.data('autads')*1;
            $.post('',{'getDateServ':true},function(response){
               var dias_dif=fecha_caduca.diff(response['hoy'], 'days');
               var documento_sel=tic_cod_sel.text().split('-')[1];
               if(dias_dif<=dias_aviso && Nota_CreDeb===false){//dias de aviso
                  alertaAuto(`Su bloc de <b> ${documento_sel}S</b> caduca en <b>${dias_dif} dias </b> `,'#Tic_Cod','left_top');}
            },'json').fail(function(){$.alert();});

         }else{
            $('#div_check_comp').addClass('hidden');
         }

         if(rise||Tic_Sri===0||Tic_Sri===4||Tic_Sri===5){
            items.jqGrid('hideCol','Ret_Ren_Sri');
            items.jqGrid('hideCol','Iva_Ren_Sri');
         }else{
            items.jqGrid('showCol','Ret_Ren_Sri');
            items.jqGrid('showCol','Iva_Ren_Sri');
         }
         items.jqGrid('setGridWidth', width, true);
         if(!available()){
            if(its!==max){
               $.createDialogConfirm('Se eliminar&aacute;n los items excedentes en este tipo de documento, Desea cambiar el Tipo de Documento?',null,
                  function(){
                     var dataIDs = items.getDataIDs();
                     for(var i=max;i<=its;i++){items.jqGrid('delRowData',dataIDs[i]);}
                  },
                  function(){
                     $('#Tic_Cod').val(Tic_Cod_Previo).trigger('change');
                  });
            }
         }else{
            Tic_Cod_Previo=$('#Tic_Cod').val();
         }

         validarNum(vet_num_ant);

         changeIvas();
         updateDocument();
      });

      $('.search_pec[name=Pec_Cod]').on('change',function(){ $('#serachDocDorm').find('[name=fecha_inicio]').val($(this).find('option:selected').data('inicio'));$('#serachDocDorm').find('[name=fecha_fin]').val($(this).find('option:selected').data('fin'));/*$('input[name=order]').val($(this).val());*/ });

      $('#Caj_Fec').change(function(){
         cargarDocumentos();
         setDefaultIva();
         $('#Cpc_Ven').datepicker('option','minDate',$('#Caj_Fec').val());
      });

      $('.datepickers').createDatePickers({checkAvailability:true,hideMsg:false}).mask('9999-99-99',{placeholder:'_'});

      //getDataVendedor();

      $('#Ret_Num').mask('999-999-999999999');
      $('#Ret_Num').change(function(){
         if($(this).val()*1<=0)
            $('#Ret_Num').fieldValid();
         else
            $('#Ret_Num').fieldValid(true);
      });

   });
}
var Tic_Cod_Previo;
//Funcin para setear el datepicker al periodo seleccionado
function fechas(inicio,fin,placod){
   $('#Caj_Fec').dateLimits(inicio,fin);
   $('.placod').val(placod);
   cargarDocumentos();
}


$(function(){
    if($('#prfDialog').length>0)
   $.createSearchDialog('prfDialog', [
      { label:'Cod.Int', name: "Prf_Cod", align: "center", hidden: false, key: true, width: 2 },
      { label: 'Fecha', name: 'Prf_Fec', align: "center", width: 4 },
      { label: 'Proform.', name: 'Prf_Num', align: "center", width: 3 },
      { label: 'CI/RUC', name: 'Prs_Ced', align: "center", width: 6 },
      { label: 'Cliente', name: 'Cliente',  align: "left", width: 17 },
      { label: 'Obser.', name: 'Prf_Obs', hidden: true, align: "center", width: 10 },
      { label: 'Estado', name: 'Prf_Est', align: "center", width: 5,  hidden: true,title:true},
      { label: 'Vendedor', name: 'Vnd_Cod', align: "center", width: 5,hidden: true, title:false },
      { label: $.createIcon('home'), name: 'actReg', align: "center", width: 2, formatter: 'gridButton', formatoptions: { action: verProforma, conditional: function (o) { return o.Prf_Est !== 'Inactiva';  }, icon: 'arrow-right', type: 'success', title: 'Ver proforma' } },
   ],null,null,null,{headertitles:true},{title:'Proforma'});
});

function verProforma(row) {
   $('#clieFormTemp').setData($.extend(row, { op_opciones: 'c' }));
   $('#Cliente').text(row['Cliente']);
   $('#prfDialog').dialog('close');
   $('input:text[name=t_descuento]').val(parseFloat(row['Prf_Des']).toFixed(2));
   obtenerDetalle(row);
}

//items.jqGrid('addRowData',next,$.extend(item,{index:next,Vet_Can:Vet_Can,Vet_Pru:Vet_Pru}),'last');
function totalAndDescuentoACero() {
   $('input:text[name=t_descuento], input:text[name=t_rubros]').val(parseFloat('0.00').toFixed(2));
   $('#t_subtotal').html(parseFloat('0.00').toFixed(2));
   $('#Vet_Obs').html('');
}
function obtenerDetalle(prf) {
    console.log(prf);
    $('#items').clearGrid();
    var valorSubTotal = 0;
    var porcentajePrf = 0;
    var next = $("#items").jqGrid('getCol', 'index', false, 'max');
   next = (isNaN(next) ? 1 : next + 1);
   var campoInf = '';
   if (Cof_Con === 'S') {
        $.getDataJson("", {profDetalleAjax: true, Prf_Cod: prf.Prf_Cod, Vnd_Cod: prf.Vnd_Cod}, function (resProformas) {
            resProformas.todasPrf.forEach(function (valor) {
                console.log(valor['Pld_Cod']);
                if (valor['Pld_Cod'] != null && valor['Pld_Cod'] > 0) {
                    console.log(valor);
                    $("#items").jqGrid('addRowData', next, $.extend(valor, {index: next, Vet_Can: valor['Prf_Cant'], Ite_Lar: valor['Ite_Lar'], Vet_Pru: valor['Prf_Pru'], Vet_Imp: parseFloat(valor['Prf_Imp']).toFixed(2), Pld_Cdc: valor['Pld_Cdc'], Adq_Cor: valor['Adq_Cor']}), 'last');
                } 
                else {
                   campoInf = campoInf +'<li>'+valor['Ite_Lar'] +'</li>';
                    //$.alert('El producto <u>' + valor['Ite_Lar'] + '</u> no tiene asignada <i>una cuenta contable</i>!', null, 'remove');
                    //return;
                }
                next++;
            });
           var valorTotal = (valorSubTotal - parseFloat(prf['Prf_Des'])).toFixed(2);
          if(campoInf!=""){
            $.alert('Los productos <u>' + campoInf + '</u> no tiene asignada <i>una cuenta contable</i>!', null, 'remove');
          }
            //$('#t_subtotal').html(valorSubTotal.toFixed(2));
            //$('#t_rubros').html(valorTotal);
            $('#Vet_Obs').html(prf['Prf_Obs']);
            $('#Prf_Num').text(prf['Prf_Num']);
            $('#Prf_Cod').text(prf['Prf_Cod']);
            //$('.iva_por').html(porcentajePrf);
            $('#items').startGridEdit();
            addItem({});
            updateDocument();
        });
    } else {
        if (Cof_Con === 'N') {
            $.getDataJson("", {profDetalleAjaxSC: true, Prf_Cod: prf.Prf_Cod}, function (resProformas) {
                resProformas.prfSinCuenta.forEach(function (valor) {
                    console.log(Cof_Con);
                    console.log(valor);
                    $("#items").jqGrid('addRowData', next, $.extend(valor, {index: next, Vet_Can: valor['Prf_Cant'], Ite_Lar: valor['Ite_Lar'], Vet_Pru: valor['Prf_Pru'], Vet_Imp: parseFloat(valor['Prf_Imp']).toFixed(2)}), 'last');
                });
                var valorTotal = (valorSubTotal - parseFloat(prf['Prf_Des'])).toFixed(2);
                //$('#t_subtotal').html(valorSubTotal.toFixed(2));
                //$('#t_rubros').html(valorTotal);
                $('#Vet_Obs').html(prf['Prf_Obs']);
                $('#Prf_Num').text(prf['Prf_Num']);
                $('#Prf_Cod').text(prf['Prf_Cod']);
                //$('.iva_por').html(porcentajePrf);
                $('#items').startGridEdit();
                addItem({});
                updateDocument();
            });
       }
    }


}



function pruebaCallBack(callbackObtener) {
   callbackObtener();
}


function obtenerOtrosPrf() {
   var otros = detCall;
   $.getDataJson("", { prfAdicionalAjax: true, Pro_Cod: otros.Pro_Cod }, function (detCargaPrf) {
      detCargaPrf.otroDetalle.forEach(function (detal) {
         $("#items").changeRow();
      });
   });

}

function mostrarVentas(){
   var exi_contado=false;
   $.each(doc_ventas.getCol('For_Cod'),function(index,forma_codigo){
      //console.log('forma_recuperado',forma_codigo);
      if(forma_codigo*1===1||$('#Tic_Cod').val()*1===5){
         $.alert('Solo un Documento de Este Tipo');
         exi_contado=true;
      }
   });
   if(!exi_contado){
      $('#Caja_Fecha').val($('#Caj_Fec').val());
      $('#ventasDialog').dialog('open');
      $.Search('ventas');
   }
}

function cargarDocumentos(ticod){
   var html,fecha=$('#Caj_Fec').val();
   $('#formDocumento').setData({Pun_Sri:'',Vet_Num:'',Aut_Sri:''},false);
   let tico_valor=$('#Tic_Cod').val()*1;
   if(editDoc===false){
      $('#formDocumento').setData({Pun_Sri:'',Vet_Num:'',Aut_Sri:''},false);
      html+='<option value="">Seleccione...</option>';
      $.each(array_documentos,function(i,v){
         if(fecha>=v['Aut_Fci'] && fecha<=v['Aut_Cad']){
            html+='<option value='+v['Tic_Cod']+' data-autads='+v['Aut_Ads']+' data-autadv='+v['Aut_Adv']+' data-ticcod='+v['Tic_Cod']+' data-ticsri='+v['Tic_Sri']+' data-puncod='+v['Pun_Cod']+' data-autcod='+v['Aut_Cod']+' data-autsri='+v['Aut_Sri']+' data-auttem='+v['Aut_Tem']+' data-autima='+v['Aut_Ima']+' data-punsri='+v['Pun_Sri']+' data-sucsri='+v['Suc_Sri']+' data-autini='+v['Aut_Ini']+' data-autfin='+v['Aut_Fin']+' data-autfci='+v['Aut_Fci']+' data-ticdes="'+v['Tic_Des']+'" data-autcad='+v['Aut_Cad']+'>'+v['Tic_Sri']+' - '+v['Tic_Des']+'</option>';
         }
      });
      $('#Tic_Cod').html(html);
      if(tico_valor>0)$('#Tic_Cod').val(tico_valor).trigger('change');
   }
   else{

      $.post('',{'Aut_Cod':AutCod,'edit_doc':editDoc,'Tic_Cod':TicCod,cargarDocumentos:true},function(response){
         html+='<option value="">Seleccione...</option>';
         $.each(response,function(i,v){
            if(fecha>=v['Aut_Fci'] && fecha<=v['Aut_Cad']){
               html+='<option value='+v['Tic_Cod']+' data-autads='+v['Aut_Ads']+' data-autadv='+v['Aut_Adv']+' data-ticcod='+v['Tic_Cod']+' data-ticsri='+v['Tic_Sri']+' data-puncod='+v['Pun_Cod']+' data-autcod='+v['Aut_Cod']+' data-autsri='+v['Aut_Sri']+' data-auttem='+v['Aut_Tem']+' data-autima='+v['Aut_Ima']+' data-punsri='+v['Pun_Sri']+' data-sucsri='+v['Suc_Sri']+' data-autini='+v['Aut_Ini']+' data-autfin='+v['Aut_Fin']+' data-autfci='+v['Aut_Fci']+'data-ticdes="'+v['Tic_Des']+'" data-autcad='+v['Aut_Cad']+'>'+v['Tic_Sri']+' - '+v['Tic_Des']+'</option>';
            }
         });
         $('#Tic_Cod').html(html);
         if(tico_valor>0)$('#Tic_Cod').val(tico_valor).trigger('change');
         if($.varValid(ticod)){
            $('#Tic_Cod').val(ticod).trigger('change');
         }
      },'json').fail(function(){$.alert();});
   }
}

//Funcin para obtener el nmero de secuencia y validar el mismo
var num_old;
function validarNum(vet_num_ant){
   if($('#Tic_Cod').val()*1>0){
      var vet_num=$('#Vet_Num').val(),sel_tcompr=$('#Tic_Cod').find('option:selected').data();
      var documento_sel=$('#Tic_Cod').find('option:selected').text().split('-')[1];
      $.post('',{'Tic_Cod':sel_tcompr['ticcod'],'Aut_Sri':sel_tcompr['autsri'],'Pun_Sri':sel_tcompr['punsri'],'Aut_Cod':sel_tcompr['autcod'],'numeroSec':true},function(response){
            var vnum=((editDoc)?(!$.vv(response['Aut_Cod']) || AutCod*1===response['Aut_Cod']*1?vet_num_ant:response['Vet_Num']):response['Vet_Num']);
         $('#formDocumento').setData({'Pun_Sri':sel_tcompr['sucsri']+'-'+sel_tcompr['punsri']+'-','Vet_Num':vnum,'Aut_Sri':(sel_tcompr['auttem']==='E'?'Electr&oacute;nica':sel_tcompr['autsri'])},false);
            //$('#formDocumento').setData({'Pun_Sri':sel_tcompr['sucsri']+'-'+sel_tcompr['punsri']+'-','Vet_Num':((editDoc)?vet_num_ant:response['Vet_Num']),'Aut_Sri':(sel_tcompr['auttem']==='E'?'Electr&oacute;nica':sel_tcompr['autsri'])},false);
         var doc_disponibles=(sel_tcompr['autfin']*1-sel_tcompr['autini']*1)-response['contador'];
         if(doc_disponibles<=sel_tcompr['autads']*1&& Nota_CreDeb===false)
            alertaAuto(`Quedan <b>${doc_disponibles} ${documento_sel}S</b> disponibles`,'#Vet_Num','right');
         validarTic_Cod(true);
         num_old=response.Vet_Num;
      },'json').fail(function(){$.alert();});
   }
}

function cargarFormasPago(){
   $('#Pag_Pld_Nota').empty();
   $('#For_Cod_Nota').empty();
   $.post('',{'getForCod':true},function(resp){

      $.each(resp['data'],function(index,item){
         var opcion=$('<option></option>').attr('value',item.For_Cod).text(item.For_Des).data(item);
         $('#For_Cod_Nota').append(opcion);
      });
      $.post('',{'buscarCuentas':true,'Pla_Cod':$('#Pec_Cod').find(':selected').data('placod')},function(resp){
         $.each($.merge(resp['Contado'],resp['Credito']),function(index,item){
            //console.log(item);
            var opcion=$('<option></option>').attr('value',item.Pld_Cod).attr('forma',validaOpcion(item)).text(item.Pld_Des).data(item);
            $('#Pag_Pld_Nota').append(opcion);
         });
         $('#For_Cod_Nota').trigger('change');
      },'json').fail(function(){
         (Conf_Con==='S'?$.alert('Sin cuentas asociadas a Pagos'):'');
      });
   },'json').fail(function (){ $.alert('Error al buscar las Formas de Pago'); });
}


function validaOpcion(item){
   var tipo='';
   if($.varValid(item.Cpc_Cxc)){
      tipo=2;
   }
   if($.varValid(item.Ban_Tip)){
      if(item.Ban_Tip==='C'){
         tipo=1;
      }
   }
   if($.varValid(item.Tpa_Abr)){
      if(item.Tpa_Abr==='CBA'){
         tipo=1;
      }
   }
   return tipo;
}

function filtrarCuentasFormasPago(dataFormaPago){
   $('#Pag_Pld_Nota').children().addClass('hidden');
   if(!$.isEmptyObject(dataFormaPago) && Cof_Con==='S'){
      var elemento=$('#Pag_Pld_Nota').find('option[forma='+dataFormaPago.For_Cod*1+']').removeClass('hidden').val();
      $('#Pag_Pld_Nota').val(elemento);
   }
}


function validarTic_Cod(generado=false){
   var vet_num=$('#Vet_Num').val(),sel_tcompr=$('#Tic_Cod').find('option:selected').data();
   var valid=true;
   if((vet_num)!==''){
      if(vet_num<sel_tcompr['autini'] || vet_num>sel_tcompr['autfin']){
         if(!generado){
            $('#Vet_Num').fieldValid(false,'El n&uacute;mero '+vet_num+' no esta en el rango ('+sel_tcompr['autini']+' - '+sel_tcompr['autfin']+')');
            valid=false;
         }else{
            $('#Vet_Num').fieldValid('warning','No tiene mas documentos de este tipo');
            $('#Vet_Num').val('');
            valid=false;
         }
         return false;
      }else{
         $.post('',{'Aut_Sri':sel_tcompr['autsri'],'Vet_Num':vet_num,'Pun_Sri':sel_tcompr['punsri'],'existeNumdoc':true},function(response){
            if(response['existe']===true && vet_num !== vet_num_ant){
               $('#Vet_Num').fieldValid(false,'El n&uacute;mero '+vet_num+' ya se encuentra registrado');
               valid=false;
            }else{
               $('#Vet_Num').fieldValid(true);
               valid=true;
            }
         },'json').fail(function(){$.alert();});
      }
   }else{
      $('#Vet_Num').fieldValid(false,'Escriba un Numero de Documento');
      valid=false;
   }
   return valid;
}


var array_contado=[],array_credito=[];
function cuentas(Pla_Cod){
   $.post('',{'Pla_Cod':Pla_Cod,buscarCuentas:true},function(response){
      array_contado=response['contado'];
      array_credito=response['credito'];
   },'json').fail(function(){$.alert();});
}

function checkCuentaPago(Pld_Cod){
   if($('#Cop_Fec').val()===''||$('#For_Cod').val()===''||Cof_Con==='N') return;
   $('#Pag_Pld').attr('disabled','disabled');
   $.post( '',{cuentasPago:true,For_Cod:$('#For_Cod').val(),Cop_Fec:$('#Cop_Fec').val(),Pld_Cod:Pld_Cod}, function( response ) {
      if(response['success']===true){
         if(response['total']>0){
            $('#Pag_Pld').html(response['cuentas']);
         }else{ $('#Pag_Pld').val('').html(''); $.alert('Error al buscar la cuenta pago para la fecha indicada');}
      }
   },'json').fail(function (){ $.alert('Error al buscar el IVA para la fecha indicada'); })
      .always(function (){ if(!$.varValid($('#Pag_Pld').data('disabled'))||$('#Pag_Pld').data('disabled')===false) $('#Pag_Pld').removeAttr('disabled'); });
}

// Selecciona Producto
// function selectItem(item){
//    var lastId=gridFact.jqGrid('getCol','index',false,'max'), close=true;
//    if(index===0){ index=lastId; close=false; }
//    gridFact.changeRow(index,$.extend(item,item['Iva_Por']*1>0?{Iva_Cod:$('#Iva_Cod').val(), Iva_Por:$('#Iva_Cod option:selected').data('ivapor'),Cop_Ice:null}:{Iva_Ren_Cod:'',Iva_Ren_Con:'',Iva_Ren_Por:'',Iva_Ren_Sri:'',Cop_Ice:null}));
//    var last=gridFact.jqGrid('getRowData',lastId);
//    if(last['Pro_Cod']!=='') addItem({});
//    if(close){ $('#proDialog').dialog('close'); setTimeout(function (){ $('#'+(index)+'_Cop_Can').focus(); },0); }else index=0;
//    updateDocument();
// }
function deletePago(Vet_Num){
   pagos.jqGrid('delRowData',Vet_Num);
   pagos.trigger('reloadGrid');
   updateDocument();
}

function deleteDocVenta(Vet_Cod){
   doc_ventas.jqGrid('delRowData',Vet_Cod);
   doc_ventas.trigger('reloadGrid');
}

function clearDocument(){
   $('#clieFormTemp').setData({});
   //$('#Tic_Cod').trigger('change');
   $('#Cop_Fec').trigger('change');
   $('#Ciu_Cod').trigger('chosen:updated');
   items.clearGrid();
   pagos.setRows([]);
   $('#Cop_Aut').attr('title','');
   addItem({});
   //validaRetNum();
   updateDocument();
   $('select[name=Tic_Cod]').attr('disabled',false);
   $('select[name=Pec_Cod]').attr('disabled',false);
   $('select[name=Cmb_Mes]').attr('disabled',false);
        if(!$.isUnd(reembolsos)){
            $('#Vet_Rem').prop('checked',false).trigger('change');
        }
}

// Guardar un cliente
function guardaCliente(){
   $.saveDataJson('',$('#clieCreateForm').getData('guardaClieAjax'), function( resp ){ 
      selectCliente(resp['clie']); $('#clieCreateDialog').dialog('close'); return false; 
   });
}

function guardaMed(){
   $.saveDataJson('',$('#medCreateForm').getData('saveMedAjax'), function( resp ){
      selectCliente(resp['clie']); $('#medCreateDialog').dialog('close'); return false;
   });
}
// Valida cedula
function validaNoIdentif(number){
   var digitos = number.split(''), dto=digitos.length, acu=0, resp={success:false,message:''},
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
      }else resp['message']='ERROR: Solo debe contener d\u00EDgitos!';
   }
   return resp;
}
function searchCliente(ced){
   $.post('',{provAjax2:true,Prs_Ced:ced.substring(0,10)}, function( response ) {
      if(response['total']*1===1){
         if(!$.varValid(response['rows'][0]['Cli_Cod'])||response['rows'][0]['Cli_Cod'].length===0){
            $('#clieCreateForm').setData(response['rows'][0]);
         }else{
            selectCliente(response['rows'][0]);
            $('#clieCreateDialog').dialog('close');
         }
      }
   },'json').fail(function (){ $('#clieCreateForm').setData({}); }).always(function (){});
}

function agregaRetencion(data){
   var form=$('#codiForm').getData(),ret={};


   $.each(data,function(k,v){
      ret[(form['tipo']==='R'?'Ret_':'Iva_')+k]=v;
   });
        ret['select']='';
   if(form['checkRentaIva']==='N')
      items.changeRow(form['index'],ret);
   else{
      //falla con la ultima fila en edicion de documentos de ventas
      var ids = items.jqGrid('getDataIDs');
      for (var i=0, z=ids.length-1; i<z; i++)
         items.changeRow(ids[i],ret);
   }
   updateDocument();
   //calculaRetencion();
   $('#codiDialog').dialog('close');
}
function remove_accent(str) {
  var accents    = '????????????????????????????????SsY??Zz';
  var accentsOut = "AAAAAAaaaaaaOOOOOOOooooooEEEEeeeeeCcDIIIIiiiiUUUUuuuuNnSsYyyZz";
  str = str.split('');
  var strLen = str.length;
  var i, x;
  for (i = 0; i < strLen; i++) {
    if ((x = accents.indexOf(str[i])) != -1) {
      str[i] = accentsOut[x];
    }
  }
  return str.join('');
}

function addPago(pago,carga_inicial=false){
   var next=pagos.jqGrid('getCol','Vet_Num',false,'max');
   var text=$('#Pag_Cod').find('option:selected').text().toUpperCase();
   pago['Vet_Num']=(isNaN(next)?1:next+1);
   pago['Tipo_Cod']=(carga_inicial?pago['Pag_Cod']:$('#Pag_Cod option:selected').val());
   pago['Forma_Cod']=$('#For_Cod option:selected').val();
   if(text==='TRANSFERENCIA'||text==='DEPOSITO'){
      pago['Pag_Pld']=(carga_inicial?pago['Pag_Pld']:$('#Ban_Cod option:selected').data('pldcod'));
   }
   
   if(text==='CHEQUE'){
      if(carga_inicial == false){
         pago['Bak_Cod'] = $('#Bak_Cod').val();
         pago['Fec_che'] = $('#Fec_che').val();
      }
   }

   if(carga_inicial && pago['Pag_Pld']*1<=0){
      pago['Pag_Pld']=$('#Pag_Pld').val();
   }
   pagos.jqGrid('addRowData',next,pago);
   pagos.trigger('reloadGrid');
   $('#pagosDialog').dialog('close');
   var pagos_tot=pagos.jqGrid('getCol', 'Vet_Tot', false, 'sum');
   //updateDocument();
   $('#For_Cod').val(1).trigger('change');
   $('.porCobrar').setData({'Val_Pcc_2':$.toFixed($('#Val_Pcc').val()-pagos_tot)});

}

function cargarCuentas(txt,pagcod){

   var selector= $('#Pag_Pld').empty();
   $.post('',{'buscarCuentas':true,'Pla_Cod':$('#Pec_Cod').find(':selected').data('placod'),'Pag_Cod':pagcod},function(resp){

      if(resp[txt]){
         var cuentas=resp[txt];
         $.each(cuentas,function(x,y){
            if(y['banco']=='no'){
               if(($('#Pag_Cod').val()*1)===(y['Pag_Cod']*1))
                  selector.append($('<option value='+y['Pld_Cod']+' data-pag_cod=\''+y['Pag_Cod']+'\'>'+y['Pld_Des']+'</option>'));}
            if (txt==='Credito'){
               selector.append($('<option value='+y['Pld_Cod']+' data-pag_cod=\''+y['Pag_Cod']+'\'>'+y['Pld_Des']+'</option>'));
            }
         });
      }else {
         $.alert('No hay cuentas parametrizadas');
      }


   },'json').fail(function (){$.alert('error inesperado'); }).always(function (){});
}


function getMayorIvaDoc(items){
   var ivaPorMayor=0;
   $.each(items,function(x,row){
      if(row['Iva_Por']>ivaPorMayor)
         ivaPorMayor=row['Iva_Por'];
   });
   return ivaPorMayor;
}

// Valida Todo antes de guardar
function validaDocument(editaDocument=false){
   if($('#t_descuento').val()*1===0) $('#Vet_Des').val(0);
   if(($('#Val_Pcc_2').val())*1<0){ $.alert('El <i><u>total de pagos</u></i> no puede Exceder al <i><u>Monto a Pagar</u></i>!<br/>Revise los Datos.',null,'remove'); return; }
   if(($('#Val_Pcc_2').val())*1>0){ $.alert('Aun queda saldo pendiente por cobrar!',null,'remove'); return; }
   var docu=$('.formDatos').getData('saveDocument'), Tic=$('#Tic_Cod option:selected');
   docu['items']=items.getGridBatch();
   for(var i=0; i<docu['items'].length; i++){
      if(Tic.data('ticsri')===0||Tic.data('ticsri')===9||Tic.data('ticsri')==2){
         eliminaRetencion({'index':docu['items'][i]['index'],'tipo':'R'});
         eliminaRetencion({'index':docu['items'][i]['index'],'tipo':'I'});
      }
   }

   docu['items']=items.getGridBatch();
   var cant_items=items.jqGrid('getDataIDs').length;
   if(cant_items<1||(cant_items<=1&&!editaDocument)){
      $.alert('Debe seleccionar al menos un <u>Item</u>!',null,'remove');
      return;
   }

   items.startGridEdit();
   docu['Tic_Txt']=$('#Tic_Cod option:selected').text();
   (isNaN(parseInt(docu['items'][docu['items'].length-1]['Pro_Cod']))?docu['items'].splice(docu['items'].length-1, 1):'');
   for(var i=0; i<docu['items'].length; i++){
      if(docu['items'][i]['Vet_Imp']*1<=0){
         $.alert('El producto <u>'+docu['items'][i]['Ite_Lar']+'</u> no puede tener <i>Importe cero</i>!',null,'remove');
         return;
      }
   }
   if(editaDocument){
      docu['Vet_Num_Ant']=vet_num_ant;
      docu['editDoc']= $('#editDoc').getData();
      if(pago_min>($('#Val_Pcc').val()*1)){
         $('.porCobrar').find('span.input-group-btn').flyout('show').focus();
         $.alert('Existen Pagos Activos por <i class="glyphicon glyphicon-usd">'+pago_min+'</i> revise el valor del documento');
         return;
      }
   }
   if($('#Ret_Fec').val()=='')
      $('#Ret_Fec').val($('#Caj_Fec').val());

   docu['Ret_Aut_Sri']=$('[name=Ret_Aut_Sri]').val();
   docu['Ret_Ren_Tot']=$('[name=Ret_Ren_Tot]').val();
   docu['rets']=$('#retencion').getGridBatch();
   docu['Plan_Cod']=$('#Pec_Cod option:selected').data('placod');
   docu['cliente']=$('#clieFormTemp [name=cliente]').text();
   docu['pagos']=pagos.getGridBatch();
   docu['Tic_Sri']=Tic.data('ticsri');
   docu['Aut_Cod']=Tic.data('autcod');
   docu['Tpc_Cod']=$('#Tpc_Cod').val();
   docu['Aut_Tem']=Tic.data('auttem');
   docu['Pun_Sri']=Tic.data('punsri');
   docu['Aut_Sri']=Tic.data('autsri');
   docu['editDocument']=editaDocument;
        docu['reembolsos']=null;
        if(!$.isUnd(reembolsos) && reembolsos.length===1 && $('#Vet_Rem').is(':checked')){
            if(("0"+$('#t_rubros').val())*1!==$.round(reembolsos.jqGrid('getCol','Total',true,'sum'))){
                return $.alert('El total de las <u class="blue">Compras de Reembolso</u> no es igual al <u class="blue">Total de Venta</u>!',null,'alert');
            }else
                docu['reembolsos']=reembolsos.getGridColumn('Cop_Cod');
        }
        console.log(docu);
   //return $.alert('OK');
   $.createDialogConfirm((editaDocument?'?Est&aacute;? seguro de editar el Documento?':'?Est&aacute;? seguro de guardar el Documento?'),docu,saveDocument);
}
function setDefaultIva(){
   if(ivas_venta.length){
      var iva_sel=ivas_venta[0]['Iva_Por'];
      var fecha_sel=$('#Caj_Fec').val();
      $.each(ivas_venta,function(i,iva){
         if(!fechaMayorQue(fecha_sel,iva['Iva_Fin'])){
            if(!fechaMayorQue(iva['Iva_Ini'],fecha_sel))
               iva_sel=iva['Iva_Por'];
         }
      });
      $('#Iva_Cod').val($('*[data-ivapor="'+iva_sel+'"]').val());
      return iva_sel;
   }
}
// Guardar documento
//data.items=$.jsonParser;
//data.pagos=$.jsonParser;
function saveDocument(data){
    $.arraySpliceFields(data.items,['select','delete','Vet_Index','Uni_Des']);
    data.items=$.jsonParser(data.items);
   $.saveDataJson('',data,
      function (resp){
         if (resp['success']){
            $('#btnVentaPrint').data('url',resp['Vet_Link']);
            $('#Vet_Impr').data('url',resp['Vet_Impr']);
            $('#resultContent').setData(resp,'name');
            $('#copForm').setData(resp['Vet_Data']);
            $('#copresult').setRows(resp['Vet_Rows']);


            $('#compForm').setData(resp['Com_Data']);
            $('#btnComPrint').data('url',resp['Com_Link']);
            $('#asiento').setRows(resp['Com_Rows']);

            $('#compFormRet').setData(resp['Com_Data_Ret']);
            $('#btnComPrintRet').data('url',resp['Com_Link_Ret']);
            $('#asientoRet').setRows(resp['Com_Rows_Ret']);

                if($.vv(resp['Xml_Path'])){
                    $('#frm_pdf_btn').show();
                    if(printers) $('.frm_ticket_btn').show();
                    $('#urlXml').val(resp['Xml_Path']);
                    $('.frm_ticket_btn').data({xml:resp['xml'],telefonos:''});
                }else{
                    $('#frm_pdf_btn').hide();
                    $('.frm_ticket_btn').hide();
                }

            if(!$.varValid(resp['Com_Rows'])){
               $('#compForm').addClass('hidden');
            }else{
               $('#compForm').removeClass('hidden');
            }

            if(!$.varValid(resp['Com_Rows_Ret'])){
               $('#compFormRet').addClass('hidden');
            }else{
               $('#compFormRet').removeClass('hidden');
            }


            if($.varValid(resp['Ret_Rows'])){
               $('#retForm').removeClass('hidden');
            }else{
               $('#retForm').addClass('hidden');
            }
            $('#retForm').setData(resp['Ret_Data']);
            $('#reteresult').setRows(resp['Ret_Rows']);
            if (data['editDocument']){
               $('#documentoMain').moveComp('#documentoResult').updateGridsSizes();
               $('select[name=Tic_Cod]').attr('disabled',false);
               $('select[name=Pec_Cod]').attr('disabled',false);
               $('select[name=Cmb_Mes]').attr('disabled',false);
            }else{
               $('.validate').find('i').removeAttr('class');
               $('#Tic_Cod').trigger('change');
               $('#factura').moveComp('#documentoResult').updateGridsSizes();
            }
             $("#anticipo").css("display", "none");
         }

      });
}

function alContado(){
   $('#For_Cod').val(1).trigger('change');
   setTimeout(function(){
      var ite_pagos=[
         $.extend($('#pagosForm').getData(),
            {'Tipo_Cod':$('#Pag_Cod option:selected').val(),
               'Forma_Cod':$('#For_Cod option:selected').val(),
               'Vet_Num':1,'Vet_Tot':$('#Val_Pcc').val(),'Pag_Pld':$('#Pag_Pld').val()
            })];
      pagos.setRows(ite_pagos);
      updateDocument();
   }, 1000);

}
function aCredito(){
   $('#For_Cod').val(2).trigger('change').attr('disabled','disabled');
   pagos.clearGrid();
   updateDocument();
   $('#pagosDialog').dialog('open');
   $('.saldos').setData({Vet_Tot:$('#Val_Pcc_2').val()});
}
function fechaMayorQue(fechaInicial,fechaFinal)
{
   valuesStart=fechaInicial.split('-');
   valuesEnd=fechaFinal.split('-');
   // Verificamos que la fecha no sea posterior a la fecha final
   var dateStart=new Date(valuesStart[0],(valuesStart[1]-1),valuesStart[2]);
   var dateEnd=new Date(valuesEnd[0],(valuesEnd[1]-1),valuesEnd[2]);
   if(dateStart>=dateEnd)
   {
      return 1;
   }
   return 0;
}
$.fn.fmatter.ice=function(cv,opts,cObjt){ var ice_por=cObjt['Cop_Ice']||cObjt['Ice_Por']; if($.varValid(ice_por)&&ice_por!==''&&!isNaN(ice_por)&&ice_por*1>0) return ice_por+' %'; else return ''; };
$.fn.fmatter.ice.unformat=function(cv,opts,cObjt){ return cv.replace(' %',''); };
$.fn.fmatter.impRenta=function(cv,opts,cObjt){ if(!$.varValid(cObjt['Pro_Cod'])||cObjt['Pro_Cod']==='') return ''; return getRentaButton(cv,{tipo:'R',index:cObjt['index']},cObjt); };
$.fn.fmatter.impRenta.unformat=$.unformatCellHtml;
$.fn.fmatter.retIva=function(cv,opts,cObjt){ if(!$.varValid(cObjt['Pro_Cod'])||cObjt['Pro_Cod']==='') return ''; if(cObjt['Iva_Por']*1===0) return ''; return getRentaButton(cv,{tipo:'I',index:cObjt['index']},cObjt);  };
$.fn.fmatter.retIva.unformat=$.unformatCellHtml;
$.fn.fmatter.edicion=function(cv,opts,cObjt){
   if($.varValid(edicion_ventas)){
      if(cObjt['Com_Edit']==='N') return '<i title="El comprobante contable es formato anterior" class="glyphicon glyphicon-lock orange"></i>';
      if(cObjt['Vet_Est']!=='A') return '<i title="Registro Anulado/Inactivo" class="glyphicon glyphicon-remove red"></i>';
      //if(cObjt['Cpc_Edit']==='N') return '<i title="Contiene Pagos Activos" class="fa fa-money green"></i>';
      if(cObjt['Vet_Aut']==='S' && edit_reten===false) return '<i title="Documento Autorizado por SRI" class="fa fa-globe green"></i>'
   }
   return $.getGridButton(cargarDoc,cObjt);
};
// Abre dialogo producto para cambiar item
function openItemSelector(id){

   index=id; $('#proDialog').dialog().dialog('open');
   $.Search('pro');
}

// Aade un item al documento
function addItem(item,Vet_Can=1,Vet_Pru=''){
   var next=items.jqGrid('getCol','index',false,'max');
   next=(isNaN(next)?1:next+1);
   items.jqGrid('addRowData',next,$.extend(item,{index:next,Vet_Can:Vet_Can,Vet_Pru:Vet_Pru}),'last');
   items.jqGrid('editRow',next);
   return next;
}

function available(){
   var its=items.jqGrid('getDataIDs').length, max=20, seguir=true;
   
   seguir=($.isNumeric(max)?(its<max)?true:false:true);
   return seguir;

}
function selectItem(item){
   var lastId=items.jqGrid('getCol','index',false,'max'), close=true, its=items.jqGrid('getDataIDs').length,full=!available();
   if(its===0){
      addItem({});
      //$('#proDialog').dialog('close');
      lastId=1;
   }else if(!full&&items.jqGrid('getRowData',lastId)['Med_Cod']!==''){ addItem({}); lastId=lastId*1+1; }
   if(index===0){ 
      index=lastId;
      close=false; 
   }
   var new_item=$.extend(item,item['Iva_Por']*1>0?{'Iva_Cod':$('#Iva_Cod').val(), 'Iva_Por':$('#Iva_Cod option:selected').data('ivapor')}:{'Iva_Ren_Cod':'','Iva_Ren_Con':'','Iva_Ren_Por':'','Iva_Ren_Sri':''});
   var precio=$.arrayGetItem(item['Precios'],'Tpv_Des','Standar','pre_index')||{};
   items.changeRow(index,new_item,null,{Vet_Pru:precio['Pre_Pvp']});
   updateRowItem({rowId:index});
   var last=items.jqGrid('getRowData',lastId);
   if(last['Med_Cod']!==''&&available()){
      addItem({});
   }
   close=true;
   if(full){ $('#proDialog').dialog('close'); return;  }
   if(close){ $('#proDialog').dialog('close'); 
   setTimeout(function (){ 
      $('#'+(index)+'_Vet_Can').focus();
       },0); 
   }else if(available()) index=0; else index=lastId*1+1;
   updateDocument();

}

// Elimina item
function deleteItem(index){
   var row=items.jqGrid('getRowData',index), 
   lastId=items.jqGrid('getCol','index',false,'max');
   if(row['Med_Cod']!==''){
      items.jqGrid('delRowData',index);
      //if(items.jqGrid('getRowData',lastId)['Pro_Cod']!=='') addItem({});
      updateDocument();
   }
}

//Estilo para cantidad
function styleCant(e,obj,opt){
   e.style.textAlign = 'right';  e.placeholder='0';
   $(e).on('keyup',function (){
      if(isNaN(this.value)){ $(this).val('1').focus();   }
      else if (this.value % 1 !== 0){ var dec=String(this.value).split('.'); if(typeof dec[1]!=='undefined'&&dec[1].length>5) this.value=$.toFixed(this.value,5);  }
      updateRowItem(obj);
   });
}

//Estilo para precio unitario
function stylePru(e,obj,opt){
   e.style.textAlign = 'right'; e.placeholder='0.00';
   $(e).on('keyup',function (){
      if(isNaN(this.value)/*||this.value===''||(!isNaN(this.value)&&this.value*1===0)*/){ $(this).val('').focus(); }
      else if (this.value % 1 !== 0){ var dec=String(this.value).split('.'); if(typeof dec[1]!=='undefined'&&dec[1].length>8) this.value=$.toFixed(this.value,8);  }
      updateRowItem(obj);
   });
}

function updateRowItem(obj){
   var datosa=items.jqGrid('getRowData',obj['rowId']);
   var datosb=items.find('tr#'+obj['rowId']).getDataForced();
   var row=$.extend({},datosa,datosb);
   row['Vet_Imp']=row['Vet_Can']*(0+row['Vet_Pru'])*1;
   row['Vet_Imp']=row['Vet_Imp']-(('0'+row['Vet_Dec'])*1>0?row['Vet_Imp']*row['Vet_Dec']/100:0);
   items.changeRow(obj['rowId'],row);
   updateDocument();
}

function updateDocument(){
   var filaCalc=addItem({});
   var rows = items.jqGrid('getRowData'),des_val=$('#t_descuento').val(), des_por=$('#Vet_Des').val(), tot={t_subtotal:0,t_iva0:0,t_iva12:0,t_iva:0,t_ice:0,t_descuento:(isNaN(des_val)?0:des_val*1),Vet_Des:(isNaN(des_por)||des_por*1===0?0:des_por*1),t_rubros:0},
      Tic_Sri=$('#Tic_Cod').find('option:selected').data('ticsri')*1, rise=(Tic_Sri===2||Tic_Sri===9);

   for (var i=0, z=rows.length; i<z ; i++){
      var row=rows[i];
      if (row['Pro_Cod']!=='') {
         row['Vet_Imp']=(row['Vet_Imp']*1);
         row['Iva_Por']=rise?0:('0'+row['Iva_Por'])*1;
         row['Ice_Por']=('0'+row['Ice_Por'])*1;
         tot['t_subtotal']=tot['t_subtotal']+row['Vet_Imp'];
         if(row['Iva_Por']===0||rise) tot['t_iva0']=tot['t_iva0']+row['Vet_Imp'];
         else tot['t_iva12']=tot['t_iva12']+row['Vet_Imp'];
      }
   }
   tot['Vet_Des']=(tot['t_descuento']>0?(tot['t_subtotal']>=tot['t_descuento']?tot['t_descuento']*100/tot['t_subtotal']:100):tot['t_descuento']*1);

   for (var i=0, z=rows.length; i<z ; i++){
      var row=rows[i], des_glob=(tot['Vet_Des']>0?row['Vet_Imp']*tot['Vet_Des']/100:0), ice=(row['Ice_Por']>0?(row['Vet_Imp']-des_glob)*row['Ice_Por']/100:0);
      if (row['Pro_Cod']!=='') {
         if(row['Iva_Por']>0&&!rise){
            tot['t_ice']=tot['t_ice']+ice;
            tot['t_iva']=tot['t_iva']+(row['Vet_Imp']+ice-des_glob)*row['Iva_Por']/100;
         }
      }
   }
   tot['t_iva']=$.round(tot['t_iva']); tot['t_ice']=$.round(tot['t_ice']);
   tot['t_rubros']=tot['t_subtotal']+tot['t_iva']+tot['t_ice']-tot['t_descuento'];

   var pagos_tot=pagos.jqGrid('getCol', 'Vet_Tot', false, 'sum');
   $('#Val_Pcc').val( $.toFixed(tot['t_rubros']-pagos_tot) );



   $.each(tot,function (k,v){
      tot[k]=$.toFixed(v,k!=='Vet_Des'?2:10);
   });
   $('#formTotales').setData(tot);
   $('#Vet_Des').val(tot['Vet_Des']);
   //calculaRetencion();
   items.jqGrid('delRowData',filaCalc);
   return tot;

}

